FROM php:7.3-fpm-alpine3.10
#FROM php:7.3-cli
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql
RUN apk add --no-cache libzip-dev && docker-php-ext-configure zip --with-libzip=/usr/include && docker-php-ext-install zip
# Tuning PHP-FPM — RAM 458MiB: limitar workers para no agotar memoria/swap en picos
RUN sed -i \
      -e 's/^pm.max_children = .*/pm.max_children = 3/' \
      -e 's/^pm.start_servers = .*/pm.start_servers = 1/' \
      -e 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 1/' \
      -e 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 2/' \
      /usr/local/etc/php-fpm.d/www.conf
COPY . /var/www/atiende
WORKDIR /var/www/atiende
