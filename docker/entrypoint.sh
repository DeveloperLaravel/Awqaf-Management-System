
#!/bin/sh

set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is missing. Configure it in Render Environment."
    exit 1
fi

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# Discover Laravel packages
su -s /bin/sh www-data -c \
    'php artisan package:discover --ansi'

# Publish Filament assets only if the command exists
if su -s /bin/sh www-data -c \
    "php artisan list --raw" | grep -qx 'filament:assets'; then
    su -s /bin/sh www-data -c \
        'php artisan filament:assets'
fi

# Cache configuration and views
su -s /bin/sh www-data -c \
    'php artisan config:cache'

su -s /bin/sh www-data -c \
    'php artisan view:cache'

# Create public storage link when needed
if [ ! -L public/storage ]; then
    su -s /bin/sh www-data -c \
        'php artisan storage:link'
fi

# Migrations are disabled unless explicitly enabled
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    su -s /bin/sh www-data -c \
        'php artisan migrate --force'
fi

exec "$@"
