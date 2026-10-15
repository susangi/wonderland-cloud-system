# ---------- Stage 1: install PHP packages ----------
# Composer runs in its own image, so the final image does not need Composer.
FROM composer:2 AS vendor
WORKDIR /app

# Copy only the package files first. Docker caches this layer,
# so packages are downloaded again only when composer.json changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# Now copy the app code and build the class autoloader.
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---------- Stage 2: the image that runs the service ----------
FROM php:8.2-apache

# pdo_mysql lets Laravel talk to MySQL. opcache makes PHP faster.
RUN docker-php-ext-install pdo_mysql opcache && a2enmod rewrite

# Apache serves Laravel's public folder and listens on 8080,
# so the container can run as a normal user (ports below 1024 need root).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
 && sed -ri -e 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
 && sed -ri -e 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

# Laravel writes cache files here, so the www-data user must own these folders.
RUN chown -R www-data:www-data storage bootstrap/cache

# Run as a normal user, not root (security best practice).
USER www-data
EXPOSE 8080

# Cache the config from env variables, then start Apache.
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && exec apache2-foreground"]