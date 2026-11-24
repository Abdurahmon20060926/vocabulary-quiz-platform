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
RUN composer install --no-dev --no-scripts --no-autoloader

COPY package.json package-lock.json ./
RUN npm ci

# Copy application code
COPY . .

# Finalize Composer
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
