# Use official PHP image with required extensions
FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zip unzip git libonig-dev default-mysql-client && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo_mysql zip mbstring exif pcntl bcmath && \
    # Increase PHP memory limit
    echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    # Set recommended MySQL PDO settings
    echo "extension=pdo_mysql.so" > /usr/local/etc/php/conf.d/pdo_mysql.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 8000

