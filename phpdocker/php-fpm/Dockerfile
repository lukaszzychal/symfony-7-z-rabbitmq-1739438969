FROM php:8.4-fpm
RUN apt-get update && apt-get install -y curl git
RUN apt-get install -y librabbitmq-dev libssl-dev
RUN pecl install amqp
RUN docker-php-ext-enable amqp
WORKDIR "/app"

