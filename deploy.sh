#!/usr/bin/env bash
# Deployment auf klassisches PHP-Webhosting (ohne Node/Docker auf dem Server).
# Nutzung: SSH_TARGET=user@host REMOTE_DIR=/pfad/zur/app ./deploy.sh
set -euo pipefail

: "${SSH_TARGET:?SSH_TARGET fehlt (user@host)}"
: "${REMOTE_DIR:?REMOTE_DIR fehlt}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_rsa}"
SSH="ssh -i $SSH_KEY"
cd "$(dirname "$0")"

echo "› Frontend bauen"
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/app" -w /app node:22 sh -c "npm ci && npm run build"

echo "› Composer (ohne Dev-Pakete)"
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/c -v "$PWD:/app" -w /app composer:2 \
  composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

echo "› Übertragen"
rsync -az --delete -e "$SSH" \
  --exclude='.git' --exclude='.github' --exclude='.env' --exclude='.env.*' --exclude='node_modules' \
  --exclude='tests' --exclude='docker' --exclude='docker-compose.yml' \
  --exclude='/storage/app' --exclude='/storage/logs' --exclude='/storage/framework/sessions' \
  --exclude='/storage/framework/cache' --exclude='/storage/framework/views' \
  --exclude='/bootstrap/cache/*.php' --exclude='/public/storage' --exclude='/public/hot' \
  ./ "$SSH_TARGET:$REMOTE_DIR/"

echo "› Migrationen & Caches"
$SSH "$SSH_TARGET" "cd $REMOTE_DIR && mkdir -p storage/app/public storage/framework/{sessions,cache,views} storage/logs bootstrap/cache \
  && php artisan package:discover --ansi && php artisan migrate --force && php artisan storage:link 2>/dev/null; \
  php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan queue:restart"
echo "› Dev-Abhängigkeiten lokal wiederherstellen"
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/c -v "$PWD:/app" -w /app composer:2 composer install --no-interaction >/dev/null
echo "✓ Fertig"
