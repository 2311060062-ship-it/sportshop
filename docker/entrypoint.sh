#!/bin/bash
set -eu
cd /var/www

if [ -n "${MYSQL_ATTR_SSL_CA:-}" ]; then
    if [ ! -r "$MYSQL_ATTR_SSL_CA" ]; then
        echo "Cannot read MySQL CA file. Check Render Secret Files and MYSQL_ATTR_SSL_CA." >&2
        exit 1
    fi
    mkdir -p /run/app-certificates
    cp "$MYSQL_ATTR_SSL_CA" /run/app-certificates/mysql-ca.pem
    chown www-data:www-data /run/app-certificates/mysql-ca.pem
    chmod 400 /run/app-certificates/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/run/app-certificates/mysql-ca.pem
fi

export PORT="${PORT:-10000}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

mkdir -p storage
chown -R www-data:www-data storage

if [ "${RUN_SQL_IMPORT:-true}" = "true" ]; then
    su-exec www-data php docker/import-schema.php
fi

php-fpm -t
nginx -t

php-fpm -F &
pid_fpm=$!
nginx -g 'daemon off;' &
pid_nginx=$!

trap 'kill -QUIT "$pid_fpm" "$pid_nginx" 2>/dev/null || true; wait "$pid_fpm" "$pid_nginx" 2>/dev/null || true' TERM INT
wait -n "$pid_fpm" "$pid_nginx"
echo "A web server exited; stopping container" >&2
exit 1
