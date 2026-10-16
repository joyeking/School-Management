# EthioSIS (RosarioSIS fork) — production image
#
# Build context is the REPOSITORY ROOT (school-management/):
#   docker build -f deploy/Dockerfile -t ethiosis .
#   (docker compose does this for you — see deploy/docker-compose.yml)
#
# All third-party PHP libraries are vendored under classes/, so there is
# NO composer install step.

FROM php:8.4-apache

# --- System libraries required by the PHP extensions ----------------------
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# --- PHP extensions -------------------------------------------------------
# Required : mysqli, pdo_mysql, mbstring
# Recommended: gd, zip, intl, gettext  (see diagnostic.php)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        intl \
        gettext \
    && a2enmod rewrite headers

# --- Recommended php.ini values (INSTALL.md) ------------------------------
COPY deploy/php.ini /usr/local/etc/php/conf.d/zz-rosariosis.ini

# --- Entrypoint: render config.inc.php from environment variables ---------
COPY deploy/entrypoint.sh /usr/local/bin/rosario-entrypoint
RUN chmod +x /usr/local/bin/rosario-entrypoint

# --- Application code -----------------------------------------------------
COPY . /var/www/html/

# Do not serve the deployment kit from the web root.
RUN rm -rf /var/www/html/deploy /var/www/html/.dockerignore

# --- Writable runtime directories ----------------------------------------
RUN mkdir -p \
        /var/www/html/assets/FileUploads \
        /var/www/html/assets/StudentPhotos \
        /var/www/html/assets/UserPhotos \
    && chown -R www-data:www-data /var/www/html/assets \
    && chmod -R 775 /var/www/html/assets

EXPOSE 80

ENTRYPOINT ["rosario-entrypoint"]
CMD ["apache2-foreground"]
