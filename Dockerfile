FROM php:8.1-cli

RUN apt-get update -y && apt-get install -y libmcrypt-dev libpq-dev unzip nodejs npm

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_pgsql

WORKDIR /app
COPY . /app
COPY .env.example /app/.env

RUN composer install

RUN php artisan key:generate
RUN php artisan migrate:fresh
RUN php artisan db:seed

ENV PORT 8000 
EXPOSE 8000

RUN chmod +x ./scripts/start.sh

CMD ["sh","./scripts/start.sh"]
