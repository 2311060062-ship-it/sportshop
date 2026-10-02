FROM php:8.2-fpm-alpine

RUN apk add --no-cache bash nginx curl gettext su-exec tini ca-certificates \
        libpng libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        libpng-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli mbstring zip gd opcache \
    && apk del .build-deps

WORKDIR /var/www
COPY . .
COPY docker/nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint

RUN chmod 755 /usr/local/bin/app-entrypoint \
    && mkdir -p /run/nginx /var/www/storage \
    && chown -R www-data:www-data /var/www/storage

EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl --fail --silent "http://127.0.0.1:${PORT:-10000}/up" > /dev/null || exit 1
ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/app-entrypoint"]
