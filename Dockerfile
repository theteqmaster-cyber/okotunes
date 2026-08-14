FROM php:8.2-apache

# Enable Apache mod_rewrite & mod_headers
RUN a2enmod rewrite headers

# Configure Apache virtualhost
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

EXPOSE 80

CMD ["apache2-foreground"]
