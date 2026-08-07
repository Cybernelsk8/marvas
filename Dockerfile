# =========================================================
# Dockerfile para Laravel 13 + Livewire + Flux UI en Railway
# =========================================================

# ---------- Stage 1: Dependencias de Composer ----------
# Usamos php:8.3-cli (misma versión que la imagen final) y copiamos
# el binario de composer desde la imagen oficial. Las imágenes
# oficiales de PHP sobre Debian ya incluyen las herramientas de
# compilación necesarias, así que docker-php-ext-install funciona
# directo, sin instalar gcc/autoconf a mano como en Alpine.
FROM php:8.3-cli AS composer_deps

WORKDIR /app

# bcmath (y cualquier otra extensión que tu composer.lock requiera
# para resolver dependencias, ej. dragon-code/support) debe estar
# presente aquí, en el mismo entorno donde corre "composer install".
RUN docker-php-ext-install bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

# Si usas Flux UI PRO (paquete privado de Composer), pasa las
# credenciales como build secret en vez de ARG/ENV (evita que
# queden grabadas en las capas de la imagen). En Railway, define
# la variable COMPOSER_AUTH en el servicio y monta el secreto así:
#
#   RUN --mount=type=secret,id=COMPOSER_AUTH \
#       COMPOSER_AUTH="$(cat /run/secrets/COMPOSER_AUTH)" \
#       composer install --no-dev --no-scripts --no-autoloader --prefer-dist
#
# Si NO usas Flux Pro, deja la línea simple de abajo tal cual.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev


# ---------- Stage 2: Build de assets con Node/pnpm ----------
FROM node:22-alpine AS node_deps

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