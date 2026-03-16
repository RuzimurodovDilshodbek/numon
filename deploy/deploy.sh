#!/bin/bash
# NurCRM — Production deploy script
# Ishlatish: bash deploy.sh

set -e

APP_DIR="/var/www/nurcrm"
PHP="php"
ARTISAN="$PHP $APP_DIR/artisan"

echo "🚀 NurCRM deploy boshlandi..."

cd $APP_DIR

# 1. Maintenance mode
$ARTISAN down --retry=15

# 2. Git pull
git pull origin main

# 3. Composer
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Migrations
$ARTISAN migrate --force

# 5. Cache tozalash va qayta yig'ish
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache
$ARTISAN filament:optimize
$ARTISAN icons:cache

# 6. Queue restart
$ARTISAN queue:restart

# 7. Horizon restart
$ARTISAN horizon:terminate

# 8. PHP-FPM reload
sudo systemctl reload php8.3-fpm

# 9. Supervisor restart
sudo supervisorctl restart nurcrm:*

# 10. Maintenance off
$ARTISAN up

echo "✅ Deploy muvaffaqiyatli tugadi!"
echo "🌐 https://numon-hoca.hujjatlar.uz"
