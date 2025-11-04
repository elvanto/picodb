FROM php:8.3-cli

RUN apt-get update && apt-get install -y gnupg2
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /etc/apt/trusted.gpg.d/packages.microsoft.gpg
RUN curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list

RUN apt-get update && \
    apt-get install -y libpq-dev unixodbc-dev && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql18 && \
    pecl install sqlsrv && \
    pecl install pdo_sqlsrv && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install pdo pgsql pdo_mysql pdo_pgsql mysqli && \
    docker-php-ext-enable pdo pdo_mysql pdo_pgsql mysqli sqlsrv pdo_sqlsrv