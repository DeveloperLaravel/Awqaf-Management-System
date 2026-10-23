#!/bin/sh

set -eu

cd /var/www/html

# يجب ضبط APP_KEY في متغيرات بيئة Render
if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is missing. Configure it in Render Environment."
    exit 1
fi

# تجهيز المجلدات التي يحتاج Laravel إلى الكتابة فيها
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# اكتشاف حزم Laravel
su -s /bin/sh www-data -c 'php artisan package:discover --ansi'

# نشر ملفات أصول Filament عند توفر الأمر في الإصدار المثبت
if php artisan list --raw | grep -qx 'filament:assets'; then
    su -s /bin/sh www-data -c 'php artisan filament:assets'
fi

# إنشاء ملفات الإعدادات المخبأة بعد ضبط متغيرات البيئة
su -s /bin/sh www-data -c 'php artisan config:cache'
su -s /bin/sh www-data -c 'php artisan view:cache'

# إنشاء رابط الملفات العامة إذا لم يكن موجودًا
if [ ! -L public/storage ]; then
    su -s /bin/sh www-data -c 'php artisan storage:link'
fi

# لا تُشغّل الترحيلات تلقائيًا إلا عند طلب صريح
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    su -s /bin/sh www-data -c 'php artisan migrate --force'
fi

exec "$@"