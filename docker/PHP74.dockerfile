FROM php:7.4-cli-alpine

# Instala extensões básicas de sistema que o Composer pode precisar (como zip)
RUN apk add --no-cache unzip libzip-dev zip postgresql-dev \
    && docker-php-ext-install zip pdo_pgsql pgsql

# Copia o Composer oficial para dentro do nosso container
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
