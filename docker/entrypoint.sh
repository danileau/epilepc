#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

echo "[entrypoint] PHP $(php -r 'echo PHP_VERSION;')"

# Pre-create writable dirs + composer cache, owned by the host-mapped UID.
mkdir -p var/cache var/log /var/www/.composer
chown -R www-data:www-data var /var/www/.composer || true

# Composer install if vendor is missing (first boot or after rm -rf vendor).
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] composer install (this can take a few minutes on a cold cache)"
    # Run as www-data so the resulting vendor/ ends up host-writable.
    su -s /bin/bash -c 'composer install --no-interaction --prefer-dist --no-progress' www-data
else
    echo "[entrypoint] vendor present — skipping composer install (run manually if composer.lock changed)"
fi

# Re-assert ownership in case the bind-mount root differs from www-data.
chown -R www-data:www-data var || true

# Defuse encryption key — auto-generated on first boot, persisted in the
# bind-mount so subsequent boots reuse it. WITHOUT this every restart
# would produce unreadable @Encrypted columns.
if [ ! -f .Defuse.key ]; then
    echo "[entrypoint] generating .Defuse.key"
    php -r 'require "vendor/autoload.php"; $k = \Defuse\Crypto\Key::createNewRandomKey(); file_put_contents(".Defuse.key", $k->saveToAsciiSafeString());'
    chmod 600 .Defuse.key
fi

# Wait until db reports schema-ready, then migrate.
echo "[entrypoint] waiting for db…"
for i in {1..30}; do
    if php bin/console doctrine:query:sql 'SELECT 1' --no-ansi >/dev/null 2>&1; then
        break
    fi
    sleep 1
done

echo "[entrypoint] doctrine:migrations:migrate"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || \
    echo "[entrypoint] WARN — migrations failed; continuing so you can debug interactively"

# Optional first-boot seed. Re-runnable; the command is idempotent.
if [ "${SEED_ON_BOOT:-0}" = "1" ]; then
    USERS="${SEED_USERS:-2}"
    echo "[entrypoint] seeding $USERS demo users"
    php bin/console app:seed-demo --users="$USERS" --no-interaction || \
        echo "[entrypoint] WARN — seed failed; you can re-run manually with: docker compose exec app bin/console app:seed-demo"
fi

echo "[entrypoint] launching: $*"
exec "$@"
