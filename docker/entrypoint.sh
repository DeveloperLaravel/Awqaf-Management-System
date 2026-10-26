
#!/bin/sh
set -eu

cd /var/www/html

# إعداد مجلدات Laravel
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# ضبط الصلاحيات
chown -R www-data:www-data storage bootstrap/cache

# إنشاء مفتاح التطبيق إذا لم يكن مضبوطًا
if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not configured."
    exit 1
fi

# تشغيل Apache
exec "$@"
