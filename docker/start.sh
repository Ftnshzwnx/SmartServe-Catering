#!/bin/bash
set -e

echo "=== Setting up Laravel ==="

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Clear caches first
php artisan config:clear
php artisan cache:clear

# Run migrations
php artisan migrate --force

# Cache config for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Starting services ==="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
