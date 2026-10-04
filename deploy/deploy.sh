#!/usr/bin/env bash
# ===== QURBA: deploy latest code =====
# Run as the qurba user:  bash deploy/deploy.sh          (first time: --first)
set -euo pipefail
cd /var/www/qurba
# PHP binary for Qurba (shared servers: PHP_BIN=php8.3, so the server's default php is never used)
PHP=${PHP_BIN:-$(command -v php8.3 || command -v php)}
php() { "$PHP" "$@"; }
composer() { "$PHP" "$(command -v composer)" "$@"; }
# Node 20 from nvm when installed for this user (shared servers)
export NVM_DIR="$HOME/.nvm"
if [ -s "$NVM_DIR/nvm.sh" ]; then set +u; . "$NVM_DIR/nvm.sh"; nvm use 20 >/dev/null || true; set -u; fi

echo "==> Maintenance mode"
php artisan down --retry=15 >/dev/null 2>&1 || true
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

echo "==> Code"
git checkout -- public/sw.js 2>/dev/null || true
git pull --ff-only

echo "==> PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Frontend build"
npm ci --no-audit --no-fund
npm run build
# New service worker version on every deploy, so phones pick up the update
sed -i "s/^const VERSION = '.*';/const VERSION = '$(git rev-parse --short HEAD)';/" public/sw.js

if [ "${1:-}" = "--first" ]; then
  php artisan key:generate --force
  php artisan storage:link || true
fi

echo "==> Database"
php artisan migrate --force

echo "==> Caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:assets

chmod -R ug+rwX storage bootstrap/cache
# Audio uploaded in /admin is written by php-fpm (group www-data)
mkdir -p public/audio/names public/audio/names-kids public/audio/duas
chgrp -R www-data public/audio 2>/dev/null || true
chmod -R ug+rwX public/audio && find public/audio -type d -exec chmod g+s {} +
sudo systemctl restart qurba-queue 2>/dev/null || php artisan queue:restart

echo "==> Integrity"
php artisan qurba:verify-quran || echo "!! Quran not imported yet: run deploy/import-content.sh"
echo "Deployed $(git rev-parse --short HEAD)"
