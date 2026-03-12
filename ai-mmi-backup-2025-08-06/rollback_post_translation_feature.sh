#!/bin/zsh
set -euo pipefail

cd "$(dirname "$0")"

php artisan migrate:rollback --path=database/migrations/2026_03_12_000001_create_member_post_translations_table.php --force || true

git checkout translation-feature-start-20260312 -- \
  app/Models/Posts.php \
  app/Http/Controllers/Web/Account.php \
  app/Http/Controllers/Admin/Posts.php \
  app/Http/Controllers/Web/Posts.php \
  resources/views/web/account_posts_publish.blade.php \
  resources/views/web/posts_details.blade.php \
  resources/lang/en/_web.php \
  resources/lang/zh-hant/_web.php \
  resources/lang/zh-hans/_web.php

rm -f \
  app/Services/PostTranslationService.php \
  app/Console/Commands/TranslateMissingPosts.php \
  database/migrations/2026_03_12_000001_create_member_post_translations_table.php

php artisan view:clear
php artisan cache:clear

echo "Post translation feature rolled back to translation-feature-start-20260312"
