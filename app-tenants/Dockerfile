FROM php:8.1-fpm-alpine

RUN apk add --no-cache libcurl curl-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql curl zip \
    && docker-php-ext-enable pdo_mysql

WORKDIR /var/www/pedidos-platform
