FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli gd
RUN a2enmod rewrite

RUN mkdir -p /var/www/html/logs \
             /var/www/html/uploads/fonds \
             /var/www/html/uploads/modeles_invitation \
             /var/www/html/uploads/photos \
             /var/www/html/uploads/photos_host

RUN chmod -R 777 /var/www/html/logs /var/www/html/uploads

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
