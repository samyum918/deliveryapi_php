FROM php:7-alpine

RUN apk add --no-cache bash
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY . /deliveryapi
WORKDIR /deliveryapi

EXPOSE 8080