#!/bin/sh
set -e

# Notwendige Verzeichnisse erstellen, falls sie nicht existieren
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Berechtigungen für Storage und Cache beheben (für den neuen 'www' Benutzer)
chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Installiere Composer-Pakete
composer install --no-interaction --prefer-dist --optimize-autoloader

# WICHTIG: Leere alle Laravel-Caches, um veraltete Konfigurationen zu entfernen
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Generiere APP_KEY, falls nicht gesetzt
if [ -z "$(awk -F= '/^APP_KEY=/ {print $2}' .env)" ]; then
    echo "Generating app key..."
    php artisan key:generate
fi

# Führe Migrationen aus
echo "Running database migrations..."
php artisan migrate --force

# Führe den eigentlichen Container-Befehl aus (php-fpm)
exec "$@"