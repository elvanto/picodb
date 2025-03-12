#!/bin/bash

# Exit on any error
set -e

# Check for parameters (PHP version and MySQL version)
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <php_version> <mysql_version>"
    echo "Example: $0 7.4 5.7"
    echo "Example: $0 8.1 8.0"
    echo "Note that MySql container will remain running to make conceive runs faster."
    exit 1
fi

PHP_VERSION=$1
MYSQL_VERSION=$2

# Get the current folder name
CURRENT_FOLDER=$(basename "$PWD")

# Define Docker container names based on the current folder and versions
PHP_CONTAINER_NAME="${CURRENT_FOLDER}-php-${PHP_VERSION}"
MYSQL_CONTAINER_NAME="${CURRENT_FOLDER}-mysql-${MYSQL_VERSION}"

# Check if the PHP image exists, if not pull it
if ! docker image inspect php:$PHP_VERSION-cli > /dev/null 2>&1; then
    docker pull php:$PHP_VERSION-cli || docker pull --platform linux/amd64 php:$PHP_VERSION-cli
fi

# Check if the MySQL image exists, if not pull it
if ! docker image inspect mysql:$MYSQL_VERSION > /dev/null 2>&1; then
    docker pull mysql:$MYSQL_VERSION || docker pull --platform linux/amd64 mysql:$MYSQL_VERSION
fi

# Check if MySQL container is already running
if [ -z "$(docker ps -a -q -f name=$MYSQL_CONTAINER_NAME)" ]; then
    # Run MySQL container
    docker run --name $MYSQL_CONTAINER_NAME -d \
      -e MYSQL_ROOT_PASSWORD=rootpassword \
      -e MYSQL_DATABASE=picodb \
      -p 3306:3306 \
      mysql:$MYSQL_VERSION

    # Wait for MySQL to start only if it was just created
    echo "Waiting for MySQL to start..."
    sleep 20
else
    echo "MySQL container $MYSQL_CONTAINER_NAME already exists."
fi

# Disable stop on error from here.
set +e

# Run unit tests inside php container...
docker run --name $PHP_CONTAINER_NAME --rm \
  --link $MYSQL_CONTAINER_NAME:mysql \
  -v $(pwd):/app \
  -w /app \
  php:$PHP_VERSION-cli bash -c "./vendor/bin/phpunit tests"

EitCode=$?


if [ $EitCode -ne 0 ]; then
    echo "❌ Tests failed for PHP $PHP_VERSION and MySQL $MYSQL_VERSION"
    exit 1
fi

echo "✅ Finished running tests for PHP $PHP_VERSION and MySQL $MYSQL_VERSION"
