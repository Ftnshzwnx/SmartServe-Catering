#!/bin/bash
set -e

echo "=== Installing PHP dependencies ==="
composer install --no-dev --optimize-autoloader

echo "=== Installing Node dependencies ==="
npm ci

echo "=== Building frontend assets ==="
npm run build

echo "=== Running Laravel setup ==="
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "=== Build complete! ==="
