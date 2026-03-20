# Use an official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application files
COPY . .

# Create storage and cache directories and set permissions
RUN mkdir -p bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# Change ownership of storage and bootstrap/cache to www-data
RUN chown -R www-data:www-data storage bootstrap/cache

# Install Composer dependencies
# If composer.json is not present, this step will fail or do nothing.
# We will assume it will be added later or handled manually.
RUN composer install --no-dev --optimize-autoloader

# Configure Apache for Laravel
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
