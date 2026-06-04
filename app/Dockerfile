FROM php:7.3-fpm-alpine3.10
#FROM php:7.3-cli
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql
RUN apk add --no-cache libzip-dev && docker-php-ext-configure zip --with-libzip=/usr/include && docker-php-ext-install zip
COPY . /var/www/atiende
WORKDIR /var/www/atiende
