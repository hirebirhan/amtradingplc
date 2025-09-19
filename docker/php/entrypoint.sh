#!/usr/bin/env sh
set -eu

cd /app

# Ensure .env exists
if [ ! -f .env ]; then
  cp .env.example .env || true
fi

# Cache cleanup to avoid stale config/routes
rm -f bootstrap/cache/*.php || true
rm -f public/hot || true

# Install composer deps only if vendor missing or empty
if [ ! -d vendor ] || [ "$(ls -A vendor 2>/dev/null || true)" = "" ]; then
  composer install --no-interaction --no-progress --prefer-dist
fi

# Generate app key if not present in .env
ENV_APP_KEY="$(grep -E '^APP_KEY=' .env | cut -d'=' -f2- | tr -d '"' 2>/dev/null || true)"
if [ "${ENV_APP_KEY}" = "" ]; then
  php artisan key:generate --force || true
fi

# Wait for DB to be ready
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
i=1
while [ "$i" -le 30 ]; do
  if php -r "try{\$pdo=new PDO('mysql:host=${DB_HOST};port=${DB_PORT}','${DB_USERNAME:-root}','${DB_PASSWORD:-root}',[PDO::ATTR_TIMEOUT=>2]);echo 'ok';}catch(Exception \$e){ }" 2>/dev/null | grep -q ok; then
    echo "MySQL is up."
    break
  fi
  echo "MySQL not ready yet (${i}/30), retrying..."
  sleep 2
  if [ "$i" -eq 30 ]; then
    echo "MySQL did not become ready in time." >&2
  fi
  i=$((i+1))
done

# Run migrations (safe when none pending)
php artisan migrate --force || true

# Start Laravel dev server
echo "Starting Laravel dev server on http://0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
