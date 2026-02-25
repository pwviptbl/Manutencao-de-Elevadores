FROM php:8.3-cli-alpine

# ─────────────────────────────────────────
# Dependências do sistema
# ─────────────────────────────────────────
RUN apk add --no-cache \
    bash \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    shadow

# ─────────────────────────────────────────
# Extensões PHP
# ─────────────────────────────────────────
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    mbstring \
    opcache

# ─────────────────────────────────────────
# Redis extension via PECL
# ─────────────────────────────────────────
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# ─────────────────────────────────────────
# Composer
# ─────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ─────────────────────────────────────────
# Configurações PHP
# ─────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# ─────────────────────────────────────────
# Usuário não-root
# ─────────────────────────────────────────
RUN groupmod -g 1000 www-data && usermod -u 1000 www-data

WORKDIR /var/www

# ─────────────────────────────────────────
# Instalar dependências e otimizar
# ─────────────────────────────────────────
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader 2>/dev/null || true

RUN chown -R www-data:www-data /var/www

USER www-data

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
