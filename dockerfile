FROM composer:latest

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apk add --no-cache linux-headers
RUN docker-php-ext-install sockets
RUN apk add supervisor

WORKDIR "/"

COPY ./supervisord.conf /etc/

RUN git clone -b master https://github.com/iatulb/patient.git

WORKDIR "/patient"

RUN git pull origin master

RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist

EXPOSE 8080

# RUN supervisord -c /etc/supervisord.conf
#RUN php -S 0.0.0.0:8080 -t public/

#CMD ["php", "artisan", "queue:work"]

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
# CMD ["php", "-S", "0.0.0.0:8080", "-t", "public/"]
