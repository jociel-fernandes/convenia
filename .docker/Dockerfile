FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    jpegoptim optipng pngquant gifsicle \
    vim \
    libpq-dev \
    supervisor \
    default-mysql-client \
    redis-tools \
    tzdata \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configurar timezone do sistema
RUN ln -snf /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime && \
    echo "America/Sao_Paulo" > /etc/timezone

# Criar diretórios necessários
RUN mkdir -p /var/log/supervisor /var/run/supervisor

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY ./.docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

COPY ./.docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY ./.docker/scripts/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh

CMD ["/usr/local/bin/app-entrypoint.sh"]