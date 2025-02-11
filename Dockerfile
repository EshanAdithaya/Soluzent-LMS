# Use an official PHP image with Apache
FROM php:8.2-apache

# Install required dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    gnupg \
    && docker-php-ext-install zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Google Cloud SDK
RUN curl -fsSL https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key add - \
    && echo "deb https://packages.cloud.google.com/apt cloud-sdk main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list \
    && apt-get update && apt-get install -y google-cloud-sdk

# Enable required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files to the Apache document root
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install PHP dependencies with Composer
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]