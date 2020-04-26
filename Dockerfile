FROM php:7.3

RUN pecl channel-update pecl.php.net && apt-get update \
    && apt-get install zlib1g-dev libzip4 \
    && apt-get install libzip-dev zip unzip\
    && pecl install swoole \
    && pecl install pcov \
    && docker-php-ext-install zip \
    && docker-php-ext-enable swoole pcov

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ADD . /srv/github/promise

WORKDIR /srv/github/promise