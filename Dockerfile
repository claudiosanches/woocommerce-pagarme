FROM wordpress:5-php5.6-apache

WORKDIR /var/www/html

# Install wp-cli
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && php wp-cli.phar --info \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

RUN mkdir -p /var/www/html/wp-content/uploads/wc-logs \
    && chmod 777 /var/www/html/wp-content/uploads/wc-logs
