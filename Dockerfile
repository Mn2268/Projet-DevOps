# Image officielle PHP avec Apache
FROM php:8.2-apache

# Installer extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer mod_rewrite (important pour .htaccess)
RUN a2enmod rewrite

# Copier le projet dans le conteneur
COPY . /var/www/html/

# Donner les permissions
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80
EXPOSE 80
