# =========================================================
# Dockerfile para Laravel 13 + Livewire + Flux UI en Railway
# =========================================================

# ---------- Stage 1: Dependencias de Composer ----------
FROM composer:2 AS composer_deps

WORKDIR /app

COPY composer.json composer.lock ./

# Si usas Flux UI PRO (paquete privado de Composer), necesitas
# credenciales. NO subas auth.json al repo con credenciales reales.
# En su lugar, en Railway define las variables:
#   COMPOSER_AUTH  = {"http-basic":{"composer.fluxui.dev":{"username":"tu-email","password":"tu-license-key"}}}
# Railway las inyecta automáticamente como variables de entorno de build.
ARG COMPOSER_AUTH
ENV COMPOSER_AUTH=${COMPOSER_AUTH}

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev


# ---------- Stage 2: Build de assets con Node/pnpm ----------
FROM node:20-alpine AS node_deps

WORKDIR /app

COPY package.json ./
COPY pnpm-lock.yaml* package-lock.json* ./

RUN corepack enable && corepack prepare pnpm@latest --activate

# Instala dependencias y compila (siguiendo lo solicitado: npm install + pnpm build)
RUN npm install
COPY . .
RUN pnpm run build


# ---------- Stage 3: Imagen final de ejecución ----------
FROM php:8.3-fpm AS final

RUN apt-get update && apt-get install -y \
        git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip nano \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copia el código + vendor ya instalado desde el stage de composer
COPY --from=composer_deps /app /var/www

# Copia los assets ya compilados (JS/CSS de Flux, Tailwind, etc.)
COPY --from=node_deps /app/public/build /var/www/public/build

# Script de arranque (migraciones, seeders, cache, servidor)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
