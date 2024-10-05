FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash

# Install PDO_mysql
RUN docker-php-ext-install pdo_mysql mysqli


# Install, configure, but do not enable Xdebug
RUN apk --no-cache add --virtual .build-dependencies \
        autoconf \
        g++ \
        make \
        rabbitmq-c \
        rabbitmq-c-dev \
        libzip-dev \
        zip \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        icu-dev \
        linux-headers \
    && docker-php-ext-install \
        bcmath \
        sockets \
    && pecl install amqp-1.11.0beta \
    && docker-php-ext-configure gd -with-freetype --with-jpeg \
    && docker-php-ext-enable amqp \
    && docker-php-ext-install zip \
    && docker-php-ext-install gd \
    && docker-php-ext-install exif \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install posix \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install intl \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install rabbitmq-cli-consumer.
RUN apk add --update rabbitmq-c-utils

COPY xdebug.ini ${PHP_INI_DIR}/conf.d/xdebug.ini

# Install Composer
RUN apk --no-cache add zlib-dev \
    && docker-php-ext-install zip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

VOLUME /opt/project
WORKDIR /opt/project
