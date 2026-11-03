FROM php:8.4-apache AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl unzip libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
        libwebp-dev libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j1 gd intl pdo_pgsql zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY deploy/helipod/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/helipod/php.ini /usr/local/etc/php/conf.d/production.ini
COPY deploy/helipod/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY deploy/helipod/entrypoint.sh /usr/local/bin/laravel-entrypoint
RUN chmod +x /usr/local/bin/laravel-entrypoint

FROM runtime AS app
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
RUN mkdir -p storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && ln -s ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["laravel-entrypoint"]
CMD ["apache2-foreground"]
