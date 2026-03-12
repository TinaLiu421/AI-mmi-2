#!/bin/zsh
set -euo pipefail

cd "$(dirname "$0")"

git checkout pre-multilang-restore-20260312 -- \
  config/app_portal.php \
  app/Http/Controllers/CoreController.php \
  resources/views/web/common.blade.php

php artisan view:clear
php artisan cache:clear
php artisan config:clear

echo "Multilanguage restore rolled back to pre-multilang-restore-20260312"
