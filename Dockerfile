FROM php:7.2-zts

RUN pecl channel-update pecl.php.net && apt-get update \
    && apt-get install zlib1g-dev libzip4 \
    && apt-get install libzip-dev zip unzip\
    && pecl install ds \
    && pecl install swoole \
    && pecl install parallel \
    && pecl install pcov \
    && docker-php-ext-install zip \
    && docker-php-ext-enable ds swoole parallel pcov

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ADD . /srv/github/promise

WORKDIR /srv/github/promise