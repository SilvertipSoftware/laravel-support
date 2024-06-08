FROM php:8.2-fpm-alpine3.19

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    bz2 \
    calendar \
    exif \
    gd \
    gettext \
    intl \
    pcntl \
    pdo_mysql \
    shmop \
    sockets \
    zip


# install composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer
