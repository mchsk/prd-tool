# Dockerfile - PHP 8.3 + Laravel + Nginx
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY backend/ .

# Install PHP dependencies (will be done in dev, production will use --no-dev)
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage 2>/dev/null || true \
    && chmod -R 755 /var/www/html/bootstrap/cache 2>/dev/null || true

# Create PRD storage directory
RUN mkdir -p /var/www/html/storage/prds && chown -R www-data:www-data /var/www/html/storage/prds

# Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
