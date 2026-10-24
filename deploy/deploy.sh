#!/usr/bin/env bash
# Actualiza el backend en el VPS con lo último de main.
#   cd /var/www/arthub-back && ./deploy/deploy.sh
#
# No corre el seeder: solo se usa en la instalación inicial (DESPLIEGUE.md, paso 5),
# porque volvería a crear las obras de ejemplo que el admin haya editado o borrado.
set -euo pipefail
cd "$(dirname "$0")/.."

echo "→ Bajando cambios de main"
git pull --ff-only origin main

echo "→ Dependencias"
composer install --no-dev --optimize-autoloader --no-interaction

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
sudo systemctl reload php8.3-fpm
echo "✓ Backend actualizado ($(git rev-parse --short HEAD))"
