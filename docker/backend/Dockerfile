FROM php:8.2-fpm-alpine

# Argumente für User/Group ID entgegennehmen
ARG UID
ARG GID

WORKDIR /var/www/html

# Benutzer und Gruppe im Container erstellen, die zum Host passen
# Dies verhindert Dateiberechtigungsprobleme mit Volumes.
RUN addgroup -g ${GID:-1000} -S www && \
    adduser -u ${UID:-1000} -S www -G www -s /bin/sh

# System Dependencies & PHP Extensions installieren
RUN apk add --no-cache \
        libpng \
        libzip \
        libjpeg-turbo \
        freetype \
        libpq \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libpng-dev \
        libzip-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        g++ \
        make \
        autoconf \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_pgsql zip exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# PHP-FPM anweisen, den neuen 'www'-Benutzer zu verwenden
RUN sed -i "s/user = www-data/user = www/g" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/group = www-data/group = www/g" /usr/local/etc/php-fpm.d/www.conf

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Entrypoint-Skript kopieren und ausführbar machen
COPY ./docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Standardbefehl des Containers
CMD ["php-fpm"]