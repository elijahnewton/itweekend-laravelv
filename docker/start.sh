#!/usr/bin/env sh
set -eu

PORT="${PORT:-80}"

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set. Define APP_KEY in Render environment variables."
    exit 1
fi

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/content \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan optimize:clear --no-interaction
php artisan migrate --force --no-interaction

if [ -d storage/app/content ] && [ -n "$(find storage/app/content -mindepth 1 -maxdepth 1 -print -quit)" ]; then
    php artisan content:sync --no-interaction
else
    echo "No content found in storage/app/content; skipping content:sync."
fi

exec apache2-foreground
