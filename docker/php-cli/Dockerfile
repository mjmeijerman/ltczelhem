FROM php:8.2-cli-alpine

RUN apk add --no-cache bash openssh

# Install PDO_mysql
RUN docker-php-ext-install pdo_mysql

# Install, configure, but do not enable Xdebug
RUN apk --no-cache add --virtual .build-dependencies \
        autoconf \
        g++ \
        make \
        git \
        rabbitmq-c \
        rabbitmq-c-dev \
        libzip-dev \
        zip \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        mysql-client \
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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY xdebug.ini ${PHP_INI_DIR}/conf.d/xdebug.ini

VOLUME /opt/project
WORKDIR /opt/project
