FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git curl zip unzip nodejs npm \
    libpng-dev libzip-dev postgresql-dev \
    oniguruma-dev libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo pdo_pgsql pgsql \
    mbstring zip exif bcmath xml \
    && docker-php-ext-enable pdo_pgsql

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency manifests first for cache efficiency
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY package.json package-lock.json ./
RUN npm ci

# Copy application code
COPY . .

# Finalize Composer
RUN composer dump-autoload --optimize

# Build frontend assets and preserve a copy outside the bind-mounted app tree.
RUN npm run build \
    && mkdir -p /opt/quiz-public-build \
    && cp -a public/build/. /opt/quiz-public-build/

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/app/entrypoint.sh /usr/local/bin/quiz-entrypoint
RUN chmod +x /usr/local/bin/quiz-entrypoint

EXPOSE 9000
ENTRYPOINT ["quiz-entrypoint"]
CMD ["php-fpm", "-F"]
