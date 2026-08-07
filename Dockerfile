# =========================================================
# Dockerfile Optimizado para Laravel 13 + Livewire + Flux UI
# =========================================================

# ---------- Stage 1: Dependencias de Composer ----------
FROM php:8.3-cli-alpine AS composer_deps

WORKDIR /app

# Instalador rápido de extensiones PHP precompiladas
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions bcmath pdo_mysql zip intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

# Si usas Flux UI Pro con secreto de Composer:
# RUN --mount=type=secret,id=COMPOSER_AUTH \
#     COMPOSER_AUTH="$(cat /run/secrets/COMPOSER_AUTH)" \
#     composer install --no-dev --no-scripts --no-autoloader --prefer-dist

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev


# ---------- Stage 2: Build de assets con Node / pnpm ----------
FROM node:22-alpine AS node_deps

WORKDIR /app

# Habilitar pnpm nativamente
RUN corepack enable && corepack prepare pnpm@latest --activate

COPY package.json pnpm-lock.yaml* ./

# Instalación estricta usando PNPM (sin npm)
RUN pnpm install --frozen-lockfile

COPY . .
RUN pnpm run build


# ---------- Stage 3: Imagen final de ejecución ----------
FROM php:8.3-alpine AS final

WORKDIR /var/www

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# Copiar el código y librerías desde los stages previos
COPY --from=composer_deps /app /var/www
COPY --from=node_deps /app/public/build /var/www/public/build

# Script de arranque
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permisos de almacenamiento
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]