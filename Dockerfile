# Sử dụng PHP 8.2 với Apache
FROM php:8.2-fpm

# Cài đặt các extension cần thiết
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring xml bcmath

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cài đặt Laravel dependencies
WORKDIR /var/www
COPY . /var/www
RUN composer install --no-dev --no-interaction --prefer-dist

# Thiết lập quyền cho storage và bootstrap
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
