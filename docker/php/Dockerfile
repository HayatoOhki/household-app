FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y \
        git \
        unzip \
        curl \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

WORKDIR /var/www/html

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]