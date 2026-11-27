FROM php:8.2-cli

# 1. Copy official Composer binary directly (much faster than curl installer)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# 2. Copy pre-built Node.js & npm directly from official Node image 
COPY --from=node:20-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# 3. Install only required system dependencies (libpq-dev for PostgreSQL, unzip for composer)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_pgsql pcntl

RUN echo "upload_max_filesize = 64M\npost_max_size = 64M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app
COPY . /app

RUN composer install --no-interaction --prefer-dist

ENV PORT=8000 
EXPOSE 8000 8001 8080

RUN chmod +x ./scripts/start.sh

CMD ["sh","./scripts/start.sh"]
