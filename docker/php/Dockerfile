FROM php:8.3-cli

RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install pdo pgsql pdo_mysql pdo_pgsql mysqli && \
    docker-php-ext-enable pdo pdo_mysql pdo_pgsql mysqli