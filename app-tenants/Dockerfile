FROM php:8.1-fpm-alpine

RUN apk add --no-cache libcurl curl-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql curl zip \
    && docker-php-ext-enable pdo_mysql

# Tuning PHP-FPM — RAM 458MiB: limitar workers para no agotar memoria/swap en picos
RUN sed -i \
      -e 's/^pm.max_children = .*/pm.max_children = 3/' \
      -e 's/^pm.start_servers = .*/pm.start_servers = 1/' \
      -e 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 1/' \
      -e 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 2/' \
      /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/pedidos-platform
