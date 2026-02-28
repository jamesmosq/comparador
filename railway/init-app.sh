#!/usr/bin/env bash
set -e

cd /app

echo "Creating storage directories..."
mkdir -p /app/storage/framework/{sessions,views,cache,testing}
mkdir -p /app/storage/logs
mkdir -p /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

echo "Running migrations..."
php artisan migrate --force

echo "Caching config, routes and views..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "Init complete."
