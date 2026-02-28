#!/usr/bin/env bash
set -e

echo "Creating storage directories..."
mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Running migrations..."
php artisan migrate --force

echo "Caching config, routes and views..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "Init complete."
