FROM php:8.4-cli-alpine

RUN apk add --no-cache bash git unzip linux-headers autoconf g++ make \
    postgresql-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_pgsql bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/src/backend
COPY . /app

RUN addgroup -g 1000 -S app && adduser -u 1000 -S app -G app
USER app:app

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
