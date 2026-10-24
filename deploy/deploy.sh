#!/usr/bin/env bash
# Actualiza el backend en el VPS con lo último de main.
#   cd /var/www/arthub-back && ./deploy/deploy.sh
#
# No corre el seeder: solo se usa en la instalación inicial (DESPLIEGUE.md, paso 5),
# porque volvería a crear las obras de ejemplo que el admin haya editado o borrado.
set -euo pipefail
cd "$(dirname "$0")/.."
PHP_FPM="php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm"

echo "→ Bajando cambios de main"
git pull --ff-only origin main

echo "→ Dependencias"
# --ignore-platform-req=php: openspout 4.x (de Filament, solo para exportar a Excel, que no usamos)
# aún no declara PHP 8.5. Los tests pasan en 8.5; quitar la opción cuando Filament lo actualice.
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php

echo "→ Modo mantenimiento"
php artisan down --retry=15 || true

echo "→ Migraciones"
php artisan migrate --force

echo "→ Cachés"
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize || true
php artisan storage:link 2>/dev/null || true

echo "→ Listo: saliendo de mantenimiento"
php artisan up

# PHP-FPM guarda el código en caché (OPcache): se recarga para tomar la versión nueva.
sudo systemctl reload "$PHP_FPM"
echo "✓ Backend actualizado ($(git rev-parse --short HEAD))"
