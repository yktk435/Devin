FROM php:8.2-apache

MAINTAINER hamaco

ARG USERNAME=docker
ARG USER_UID=1000
ARG USER_GID=1000

RUN apt-get update && apt-get install -y libzip-dev git zip zlib1g-dev locales \
    && docker-php-ext-install bcmath pdo_mysql \
    && pecl install ast && docker-php-ext-enable ast \
    && pecl install pcov && docker-php-ext-enable pcov \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY config/php/php-override.ini /usr/local/etc/php/conf.d/php-override.ini

## Locale 設定
RUN localedef -f UTF-8 -i ja_JP ja_JP.UTF-8

## User 設定
RUN groupadd --gid $USER_GID $USERNAME
RUN useradd --uid $USER_UID --gid $USER_GID -m $USERNAME

USER $USERNAME
WORKDIR /var/www/html
