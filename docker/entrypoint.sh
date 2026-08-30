#!/bin/sh
set -e

echo "==> Esperando conexión a la base de datos..."
MAX_TRIES=15
COUNT=0
until php artisan migrate:status > /dev/null 2>&1; do
    COUNT=$((COUNT+1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "No se pudo conectar a la base de datos después de $MAX_TRIES intentos. Abortando."
        exit 1
    fi
    echo "Base de datos no disponible aún, reintentando en 3s... ($COUNT/$MAX_TRIES)"
    sleep 3
done

echo "==> Generando enlace de storage (si no existe)..."
if [ ! -L "/var/www/public/storage" ]; then
    php artisan storage:link
fi

echo "==> Publicando assets de Livewire y Flux..."
php artisan vendor:publish --tag=livewire:assets --force
php artisan vendor:publish --tag=flux:assets --force

echo "==> Cacheando configuración, rutas y vistas..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Ejecutando migraciones..."
php artisan migrate --seed --force

echo "==> Ejecutando seeders..."
# IMPORTANTE: esto corre en cada deploy. Si tus seeders no son
# idempotentes (usan firstOrCreate/updateOrCreate), generarás
# datos duplicados en cada redeploy. Ver nota en el chat.
php artisan db:seed --force

# Por esto (aprovecha la variable de entorno PORT que asigna Railway dinámicamente):

echo "==> Iniciando el servidor web en el puerto ${PORT:-8080}..."

# Opción A: Si usas php artisan serve (Recomendado para contenedores ligeros)
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"

# PORT="${PORT:-8080}"
# echo "==> Iniciando servidor en el puerto $PORT..."
# exec php -S 0.0.0.0:$PORT -t public public/index.php
