FROM php:8.3-fpm-alpine

# system + rozszerzenia PHP
RUN set -eux; \
    apk add --no-cache bash git curl icu icu-dev oniguruma-dev zlib-dev libzip-dev libpq-dev tzdata; \
    docker-php-ext-install intl opcache pdo pdo_pgsql; \
    echo "date.timezone=Europe/Warsaw" > /usr/local/etc/php/conf.d/timezone.ini; \
    echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

# composer w obrazie
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# najpierw tylko pliki composer, żeby cache się lepiej zachowywał
COPY composer.json composer.lock* /app/
RUN composer install --no-interaction --prefer-dist --no-progress || true

# dopiero teraz cała aplikacja
COPY . /app

# uprawnienia na cache/var
RUN mkdir -p var && chown -R www-data:www-data var

CMD ["php-fpm"]
