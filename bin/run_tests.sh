#!/bin/bash

# Exit on any error
set -e
# print each command being run so we can see what command had an error.
set -x

# Check for parameters (PHP version and MySQL version)
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <php_version> <mysql_version>"
    echo "Example: $0 7.4 5.7"
    exit 1
fi

PHP_VERSION=$1
MYSQL_VERSION=$2

# Define Docker container names
PHP_CONTAINER_NAME="php-test-container"
MYSQL_CONTAINER_NAME="mysql-test-container"

# Pull the necessary Docker images, forcing ARM64 if required.

# Pulling images.                   # Fallback to amd64 when no arm64 image exists.
docker pull php:$PHP_VERSION-cli || docker pull --platform linux/amd64 php:$PHP_VERSION-cli
docker pull mysql:$MYSQL_VERSION || docker pull --platform linux/amd64 mysql:$MYSQL_VERSION

# Run MySQL container
docker run --name $MYSQL_CONTAINER_NAME -d \
  -e MYSQL_ROOT_PASSWORD=rootpassword \
  -e MYSQL_DATABASE=picodb \
  -p 3306:3306 \
  mysql:$MYSQL_VERSION

# Wait for MySQL to start
echo "Waiting for MySQL to start..."
sleep 20 # Wait for MySQL to be ready, you can adjust this based on your setup

# Run unit tests inside php container...
docker run --name $PHP_CONTAINER_NAME --rm -d \
  --link $MYSQL_CONTAINER_NAME:mysql \
  -v $(pwd):/app \
  -w /app \
  php:$PHP_VERSION-cli bash -c "apt-get update && apt-get install -y libpdo-mysql php-mysql && \
    curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install && \
    ./vendor/bin/phpunit tests"

# Clean up
echo "Test run complete. Cleaning up..."

# Stop MySQL container
docker stop $MYSQL_CONTAINER_NAME
docker rm $MYSQL_CONTAINER_NAME

# PHP container should automatically stopped and removed itself.

echo "Finished running tests for PHP $PHP_VERSION and MySQL $MYSQL_VERSION"
