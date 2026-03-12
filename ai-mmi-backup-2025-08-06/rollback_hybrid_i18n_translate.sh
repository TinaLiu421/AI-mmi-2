#!/usr/bin/env bash
set -euo pipefail
TAG="pre-hybrid-i18n-translate-20260312"
if ! git rev-parse "$TAG" >/dev/null 2>&1; then
  echo "Tag $TAG not found."
  exit 1
fi
git restore --source "$TAG" config/app_portal.php app/Http/Controllers/CoreController.php resources/views/web/common.blade.php public/asset/js/web/common.js public/asset/css/web/common.css
php artisan config:clear >/dev/null 2>&1 || true
php artisan cache:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true
echo "Rollback complete: restored hybrid i18n baseline from $TAG"
