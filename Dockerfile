FROM richarvey/nginx-php-fpm:3.4.1-php84

COPY . .

# Image config
ENV SKIP_COMPOSER 0
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

ENV COMPOSER_ALLOW_SUPERUSER 1

# Install PHP extensions (Alpine - pakai apk)
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql bcmath mbstring zip

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Run migrations
RUN php artisan migrate --force || true

# Laravel optimization
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

CMD ["/start.sh"]