#!/bin/bash
set -e

# Force Laravel logs to stderr so they appear in Railway console
export LOG_CHANNEL=stderr

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

# Run migrations & seeders
php artisan migrate --force
php artisan db:seed --force

# Cache config for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions for www-data since artisan commands ran as root
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "=== Starting services ==="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
