#SAE MANAGER + MySQL
FROM php:8.2-apache

RUN docker-php-ext-install mysqli

# 2. Apache mod_rewrite
RUN a2enmod rewrite

# 3. Configuration Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

# 5. Composer Install
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Copier dans le conteneur
COPY . /var/www/html/

# 7. Installer les dépendances PHP
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 8. Donner les bons droits à Apache
RUN chown -R www-data:www-data /var/www/html

# 9. Exposer le port 80
EXPOSE 80