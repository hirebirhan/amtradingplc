#!/bin/sh

# Exit on error
set -e

# Install dependencies
composer install
npm install

# Generate app key if it doesn't exist
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Run migrations
php artisan migrate --force

# Start Vite dev server in the background
npm run dev -- --host &

# Start PHP-FPM
php-fpm
