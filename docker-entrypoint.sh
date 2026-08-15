#!/bin/bash

echo "Fixing Laravel permissions..."

mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

chmod -R 775 storage bootstrap/cache

echo "Starting PHP-FPM..."

exec "$@"
