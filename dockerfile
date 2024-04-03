FROM composer:latest

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apk add --no-cache linux-headers
RUN docker-php-ext-install sockets

WORKDIR "/"

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

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public/"]
