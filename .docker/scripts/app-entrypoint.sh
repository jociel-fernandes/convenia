#!/bin/bash

mkdir -p /var/log/supervisor /var/run/supervisor

# Garantir prioridade das variáveis de ambiente do container
if [ -f "/var/www/.env" ]; then
    mv /var/www/.env /var/www/.env.backup
fi

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

if [ -f "/var/www/artisan" ]; then
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf