#!/bin/sh
set -e

echo "==> Limpiando cachés previas..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "==> Esperando conexión a la base de datos..."
MAX_TRIES=15
COUNT=0
until php artisan db:monitor > /dev/null 2>&1 || php artisan migrate:status > /dev/null 2>&1; do
    COUNT=$((COUNT+1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "No se pudo conectar a la base de datos después de $MAX_TRIES intentos. Abortando."
        exit 1
    fi
    echo "Base de datos no disponible aún, reintentando en 3s... ($COUNT/$MAX_TRIES)"
    sleep 3
done

echo "==> Eliminando todas las tablas..."
php artisan db:wipe --force

echo "==> Ejecutando migraciones y seeders..."
php artisan migrate --seed --force

echo "==> Generando enlace de storage (si no existe)..."
if [ ! -L "/var/www/public/storage" ]; then
    php artisan storage:link
fi

echo "==> Publicando assets de Livewire y Flux..."
php artisan vendor:publish --tag=livewire:assets --force
php artisan vendor:publish --tag=flux:assets --force

echo "==> Cacheando configuración, rutas y vistas para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Iniciando el servidor web en el puerto ${PORT:-8080}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"