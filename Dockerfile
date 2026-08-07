# =========================================================
# Dockerfile Optimizado para Laravel 13 + Livewire + Flux UI
# =========================================================

# ---------- Stage 1: Dependencias de Composer ----------
FROM php:8.3-cli-alpine AS composer_deps

WORKDIR /app

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions bcmath pdo_mysql zip intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev


# ---------- Stage 2: Build de assets con Node / pnpm ----------
FROM node:22-alpine AS node_deps

WORKDIR /app

RUN corepack enable && corepack prepare pnpm@latest --activate

COPY package.json pnpm-lock.yaml* ./

RUN pnpm install --frozen-lockfile

COPY . .

# 🚨 AQUÍ ESTÁ EL CAMBIO CRÍTICO 🚨
# Copiamos la carpeta vendor instalada en Composer para que Vite/Tailwind
# encuentre las clases y archivos CSS de Flux UI (/vendor/livewire/flux/...)
COPY --from=composer_deps /app/vendor /app/vendor

RUN pnpm run build


# ---------- Stage 3: Imagen final de ejecución ----------
FROM php:8.3-alpine AS final

WORKDIR /var/www

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

COPY --from=composer_deps /app /var/www
COPY --from=node_deps /app/public/build /var/www/public/build

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 8080

# ENTRYPOINT ["entrypoint.sh"]