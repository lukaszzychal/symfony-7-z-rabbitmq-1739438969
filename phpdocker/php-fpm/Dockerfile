FROM php:8.4-fpm
RUN apt-get update && apt-get install -y curl git
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY . /app
WORKDIR "/app"

