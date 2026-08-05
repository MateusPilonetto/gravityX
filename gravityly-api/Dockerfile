FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    git curl libpng-dev libzip-dev zip unzip oniguruma-dev \
    sqlite-dev postgresql-dev \
    freetype-dev libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        pdo_pgsql \
        mbstring \
        zip \
        gd \
        bcmath \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY gravityly-api/docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

COPY gravityly-api/composer.json gravityly-api/composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY gravityly-api/ .

RUN composer dump-autoload --optimize \
    && mkdir -p database \
    && touch database/database.sqlite \
    && php artisan storage:link \
    && chown -R www-data:www-data \
        /var/www/storage \
        /var/www/bootstrap/cache \
        /var/www/database

EXPOSE 10000

CMD ["sh", "-c", "set -ex; php artisan optimize:clear; mkdir -p storage/app/public; php artisan storage:link --force; php artisan migrate --force; php artisan schedule:work & exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
