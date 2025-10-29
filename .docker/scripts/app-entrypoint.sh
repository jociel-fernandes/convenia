#!/bin/bash

mkdir -p /var/log/supervisor /var/run/supervisor

chown -R www-data:www-data /var/www
find /var/www -type d -exec chmod 755 {} \;
find /var/www -type f -exec chmod 644 {} \;

if [ -d "/var/www/storage" ]; then
    chmod -R 775 /var/www/storage
    chown -R www-data:www-data /var/www/storage
fi

if [ -d "/var/www/bootstrap/cache" ]; then
    chmod -R 775 /var/www/bootstrap/cache
    chown -R www-data:www-data /var/www/bootstrap/cache
fi

if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader --quiet
fi

if [ -f "/var/www/artisan" ]; then

    if [ ! -f "/var/www/.env" ]; then
        cp /var/www/.env.example /var/www/.env
        php artisan key:generate --force --no-interaction
    fi

    until php artisan migrate --no-interaction; do
        sleep 3
    done

    php artisan passport:keys --force --no-interaction
    php artisan passport:client --personal --name="Personal Access Client" --no-interaction

    chmod 644 /var/www/storage/oauth-public.key
    chmod 600 /var/www/storage/oauth-private.key

    php artisan db:seed --no-interaction

    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    php artisan config:cache
    php artisan route:cache

fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf