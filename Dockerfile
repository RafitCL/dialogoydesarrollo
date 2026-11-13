# Imagen base con PHP 8.2 + Apache (capa gratuita AWS EC2 / Lightsail)
FROM php:8.2-apache

# Extensiones necesarias
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli gd mbstring fileinfo opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Configuración de Apache: document root y rewrites
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar proyecto
COPY . /var/www/html

# Permisos para uploads
RUN chown -R www-data:www-data /var/www/html/uploads /var/www/html/assets/images \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
CMD ["apache2-foreground"]