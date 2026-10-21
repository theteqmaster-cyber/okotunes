FROM php:8.3-alpine

# Install curl and openssl extensions for fast API communication
RUN apk add --no-cache curl-dev \
    && docker-php-ext-install curl

# Working directory
WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html/

# Ensure data directory has write permissions for session lore and datasets
RUN mkdir -p /var/www/html/data/chats \
    && chmod -R 777 /var/www/html/data

# Expose port 80 for Render Web Service
EXPOSE 80

# Start high-performance PHP built-in server with router
CMD ["php", "-S", "0.0.0.0:80", "router.php"]
