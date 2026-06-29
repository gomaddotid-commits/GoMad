FROM php:8.4-fpm-alpine

# Install Nginx + Node.js
RUN apk add --no-cache nginx nodejs npm

# Install PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql bcmath mbstring zip

# Copy project files
COPY . /var/www/html

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Composer dependencies (pakai source/git clone biar gak kena HTTP 400)
RUN composer config -g process-timeout 1200 && \
    composer install --no-dev --optimize-autoloader --prefer-source --no-interaction || \
    composer install --no-dev --optimize-autoloader --prefer-source --no-interaction || \
    composer install --no-dev --optimize-autoloader --prefer-source --no-interaction

# Install NPM dependencies + Build Vite (dengan timeout + retry)
RUN npm config set fetch-timeout 120000 && \
    npm config set fetch-retries 5 && \
    npm install && \
    npm run build

# Hapus node_modules setelah build (biar image kecil)
RUN rm -rf node_modules

# Create storage and cache directories
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 777 storage bootstrap/cache

# Nginx configuration
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

# Run config cache + migrate saat runtime
ENTRYPOINT ["sh", "-c", "php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'"]