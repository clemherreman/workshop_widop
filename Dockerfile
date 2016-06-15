FROM php:7-alpine

RUN apk add --no-cache --virtual .persistent-deps \
        git \
        unzip

COPY . /myapp
WORKDIR /myapp

EXPOSE 7000
CMD php -S 0.0.0.0:7000 -t /myapp/web/
