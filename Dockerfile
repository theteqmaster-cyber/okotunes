FROM php:8.2-apache

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_sqlite gd zip fileinfo \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite & mod_headers
RUN a2enmod rewrite headers

# Configure Apache virtualhost / directory settings
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/okotunes.conf \
    && a2enconf okotunes

# Set working directory
WORKDIR /var/www/html

# Copy application source files
COPY . /var/www/html/

# Create data & cache directories and set permissions for Apache
RUN mkdir -p /var/www/html/data /var/www/html/cache/art \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/cache \
    && chmod -R 775 /var/www/html/data /var/www/html/cache

EXPOSE 80

CMD ["apache2-foreground"]
