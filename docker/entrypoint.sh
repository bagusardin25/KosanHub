#!/bin/sh
# Entrypoint image KosanHub (dipakai service app dan worker).
set -e

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY kosong. Isi APP_KEY di file .env (lihat .env.example)." >&2
    exit 1
fi

# Cache konfigurasi & route supaya aplikasi lebih cepat di production.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hanya service app yang menjalankan migrasi (RUN_MIGRATIONS=true di compose.yaml).
# Worker baru start setelah app healthy, jadi tabel sudah siap.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --force
fi

exec "$@"
