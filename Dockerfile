FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_mysql gd

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /var/www
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

COPY . /var/www
WORKDIR /var/www

EXPOSE 80

CMD service nginx start && php-fpm

