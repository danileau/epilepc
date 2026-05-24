# Symfony 4.4 dev image for epilepc.
# PHP 8.2 — laminas-code 4.11 and laminas-eventmanager 3.10 in the lockfile
# require ~8.1/8.2 (see project_epilepc_tech_debt memory). PHP 8.3 + this
# lockfile breaks composer install.
FROM php:8.2-apache

ARG UID=1000
ARG GID=1000

# wkhtmltopdf is not installed:
#   - it was removed from Debian Trixie (deprecated upstream, unmaintained WebKit fork)
#   - epilepc's PDF generator has been rewritten to native/client-side (commits
#     5efa0ae + d499d1a) so knplabs/knp-snappy is effectively dead code in dev
#   - if a Snappy-using code path ever fires in dev, it'll error visibly rather
#     than silently producing the wrong output
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libonig-dev libxml2-dev libsodium-dev \
        unzip git curl ca-certificates \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql intl mbstring zip opcache sodium \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Match host UID/GID so the bind-mounted source stays writable by the host.
RUN groupmod -g ${GID} www-data && usermod -u ${UID} -g ${GID} www-data

WORKDIR /var/www/html

# Apache: docroot is public/, rewrite the front controller.
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

# Entrypoint: composer install (if vendor missing) → migrations → optional seed → apache
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Source is bind-mounted at runtime via compose; nothing to COPY into the
# image. Permissions on var/cache + var/log get fixed in entrypoint.
# (The base php:8.2-apache image's default sed-rewrite of /var/www/html is
# NOT applied here — our custom apache-site.conf already points at
# /var/www/html/public directly.)

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
