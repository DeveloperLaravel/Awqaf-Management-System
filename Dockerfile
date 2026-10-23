
# syntax=docker/dockerfile:1

# ==========================================
# 1. تجهيز PHP وامتداداته
# ==========================================
FROM php:8.3-apache-bookworm AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
    && a2enmod rewrite headers \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# ==========================================
# 2. تثبيت مكتبات Laravel وFilament
# ==========================================
FROM php-base AS dependencies

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --no-scripts


# ==========================================
# 3. بناء ملفات الواجهة الأمامية
# ==========================================
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package*.json ./

RUN if [ -f package-lock.json ]; then \
        npm ci; \
    else \
        npm install; \
    fi

COPY . .

# نسخ ملفات Composer وFilament حتى يجدها Vite
COPY --from=dependencies /var/www/html/vendor ./vendor

# بناء ملفات CSS وJavaScript
RUN npm run build


# ==========================================
# 4. تجهيز صورة التطبيق النهائية
# ==========================================
FROM php-base AS production

WORKDIR /var/www/html

ENV PORT=10000

# ملفات Laravel ومكتبات Composer
COPY --from=dependencies /var/www/html /var/www/html

# ملفات الواجهة المبنية بواسطة Vite
COPY --from=frontend /app/public/build /var/www/html/public/build

# COPY --from=frontend /app/public/build ./public/build
# إعداد Apache
COPY docker/apache-vhost.conf \
    /etc/apache2/sites-available/000-default.conf

# سكربت تشغيل التطبيق
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["apache2-foreground"]
