# Laravel 11 en Render (free tier) — PHP 8.3 + PostgreSQL
FROM php:8.3-cli

# Dependencias del sistema + extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
        git curl zip unzip \
        libpq-dev libonig-dev libzip-dev libpng-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip gd \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Instala dependencias primero (mejor cache de capas)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copia el resto del proyecto
COPY . .
RUN composer dump-autoload --optimize \
    && chmod -R 775 storage bootstrap/cache

# Render asigna el puerto vía $PORT
ENV PORT=8000
EXPOSE 8000

# Al arrancar: migra, siembra y sirve la API
CMD php artisan migrate --force \
 && php artisan db:seed --force \
 && php artisan serve --host=0.0.0.0 --port=${PORT}
