FROM php:8.4-fpm-alpine

# Install Nginx and Supervisor
RUN apk add --no-cache nginx supervisor

# Install PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql bcmath mbstring zip

# Enable PHP error logging
RUN echo "error_log = /proc/self/fd/2" >> /usr/local/etc/php/conf.d/docker.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker.ini

# Copy project files
COPY . /var/www/html

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set Composer timeout
ENV COMPOSER_PROCESS_TIMEOUT=2000

# Install dependencies
RUN composer config -g repos.packagist composer https://packagist.org && \
    composer config -g github-protocols https && \
    composer install --no-dev --optimize-autoloader --prefer-dist || \
    composer install --no-dev --optimize-autoloader --prefer-dist || \
    composer install --no-dev --optimize-autoloader --prefer-dist

# Create cache directories
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Fix permissions
RUN chmod -R 777 storage bootstrap/cache

# Run migrations
RUN php artisan migrate --force || true

# Laravel optimization
RUN php artisan config:cache && \
    php artisan route:cache

# Create storage link
RUN php artisan storage:link || true

# Configure Nginx
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /var/www/html/public; \
    index index.php; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/http.d/default.conf

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]