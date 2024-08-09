#!/bin/bash

# Aktualisieren der Abhängigkeiten
npm install
composer install

# Datenbank-Migration
php artisan migrate

# Starten des Vite-Entwicklungsservers im Hintergrund
npm run build &

# Starten des Laravel-Servers
php artisan serve &

# Starten des Reverb-Servers
php artisan reverb:start &

# Warten auf Benutzer-Eingabe
echo "Server gestartet. Drücken Sie eine beliebige Taste, um alle Prozesse zu beenden."
read -n 1

# Beenden aller Hintergrundprozesse
kill $(jobs -p)