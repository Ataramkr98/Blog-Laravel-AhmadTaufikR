#!/bin/sh
set -eu
umask 0027
mkdir -p storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
php artisan config:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
exec docker-php-entrypoint "$@"
