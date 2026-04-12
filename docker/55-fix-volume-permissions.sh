#!/bin/sh
# Ensure the Railway volume mount (storage/app/public) is writable by www-data
# and that the images/generated subdirectory exists.
# This runs on every container start before php-fpm, as root.

STORAGE_PUBLIC="/var/www/html/storage/app/public"

if [ -d "$STORAGE_PUBLIC" ]; then
    # Ensure www-data can write to the volume root
    chmod 775 "$STORAGE_PUBLIC" 2>/dev/null || true
    chown www-data:www-data "$STORAGE_PUBLIC" 2>/dev/null || true

    # Pre-create the images/generated directory so php-fpm doesn't have to
    mkdir -p "$STORAGE_PUBLIC/images/generated"
    chown -R www-data:www-data "$STORAGE_PUBLIC/images"
    chmod -R 775 "$STORAGE_PUBLIC/images"

    echo "✅ Volume permissions fixed: $STORAGE_PUBLIC"
else
    echo "⚠️  Storage public dir not found: $STORAGE_PUBLIC"
fi
