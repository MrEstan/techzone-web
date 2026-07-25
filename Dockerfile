FROM php:8.2-apache

RUN a2enmod rewrite

COPY public/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    touch /var/www/html/database.sqlite && \
    chmod 666 /var/www/html/database.sqlite

EXPOSE 8080

CMD ["apache2-foreground"]
