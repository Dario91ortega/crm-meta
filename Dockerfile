# syntax=docker/dockerfile:1.7

# =============================================================================
# Base: imagen mínima con PHP-FPM + extensiones necesarias para Laravel/Filament
# =============================================================================
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        mysql-client \
        linux-headers \
    && apk add --no-cache --virtual .build-deps autoconf gcc g++ make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd \
        bcmath \
        opcache \
        exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/pear

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini


# =============================================================================
# Dev: agrega composer + node, usuario no-root con UID/GID del host
# Se usa con bind mount del código fuente (hot reload).
# =============================================================================
FROM base AS dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache nodejs npm

ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} app \
    && adduser -D -u ${UID} -G app app \
    && chown -R app:app /var/www/html

USER app
EXPOSE 9000
CMD ["php-fpm"]


# =============================================================================
# Build: instala deps de producción y compila assets (artefactos para prod)
# =============================================================================
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache nodejs npm

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY package.json package-lock.json* vite.config.* ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && npm run build \
    && rm -rf node_modules


# =============================================================================
# Prod: imagen final mínima. Sin composer ni node en runtime.
# =============================================================================
FROM base AS prod

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

RUN chmod -R 775 storage bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
