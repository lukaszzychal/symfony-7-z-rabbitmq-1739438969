FROM php:8.4-fpm
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    curl

RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql
RUN apt-get install -y librabbitmq-dev libssl-dev
RUN  pecl install amqp && docker-php-ext-enable amqp

RUN pecl install mongodb \
  && docker-php-ext-enable mongodb

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR "/app"

