#!/bin/zsh
set -euo pipefail

cd "$(dirname "$0")"

git checkout pre-visible-translate-button-20260312 -- \
  resources/views/web/common.blade.php

php artisan view:clear
php artisan cache:clear

echo "Visible translate button UI rolled back to pre-visible-translate-button-20260312"
