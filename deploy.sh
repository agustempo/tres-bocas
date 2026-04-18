#!/bin/bash
# =============================================================================
# deploy.sh — masBocas / 3 BOCAS
# Target: Ubuntu 22.04+ · PHP 8.3+ · nginx · SQLite · no Docker
# Usage: ./deploy.sh [--skip-build] [--skip-migrate]
# =============================================================================
set -euo pipefail

# ─── Config ──────────────────────────────────────────────────────────────────
APP_DIR="/var/www/masbocas"
GIT_BRANCH="main"
PHP_FPM_SERVICE="php8.3-fpm"          # adjust if using 8.4: php8.4-fpm
NGINX_SERVICE="nginx"
LOG_FILE="/var/log/masbocas-deploy.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
SKIP_BUILD=false
SKIP_MIGRATE=false

# ─── Flags ───────────────────────────────────────────────────────────────────
for arg in "$@"; do
  case $arg in
    --skip-build)   SKIP_BUILD=true ;;
    --skip-migrate) SKIP_MIGRATE=true ;;
  esac
done

# ─── Logging ─────────────────────────────────────────────────────────────────
mkdir -p "$(dirname "$LOG_FILE")"
exec > >(tee -a "$LOG_FILE") 2>&1

log()  { echo "[${TIMESTAMP}] ✔  $*"; }
warn() { echo "[${TIMESTAMP}] ⚠  $*"; }
fail() { echo "[${TIMESTAMP}] ✘  $*"; exit 1; }

echo ""
echo "============================================================"
echo "  masBocas deploy — ${TIMESTAMP}"
echo "============================================================"

# ─── Safety checks ───────────────────────────────────────────────────────────
[[ -d "$APP_DIR" ]]       || fail "App directory not found: $APP_DIR"
[[ -f "$APP_DIR/.env" ]]  || fail ".env not found. Copy .env.example and configure it first."
command -v php      &>/dev/null || fail "php not found"
command -v composer &>/dev/null || fail "composer not found"
command -v npm      &>/dev/null || fail "npm not found"
command -v git      &>/dev/null || fail "git not found"

# Warn if running as root (not ideal, but allowed on some VPS setups)
if [[ "$EUID" -eq 0 ]]; then
  warn "Running as root. Consider using a dedicated deploy user."
fi

# ─── Step 1 · Pull latest code ───────────────────────────────────────────────
log "Pulling $GIT_BRANCH from origin..."
cd "$APP_DIR"
git fetch --all --prune
git checkout "$GIT_BRANCH"
git pull origin "$GIT_BRANCH"
log "Code updated. HEAD: $(git log -1 --oneline)"

# ─── Step 2 · PHP dependencies ───────────────────────────────────────────────
log "Installing PHP dependencies (no-dev)..."
composer install \
  --no-dev \
  --no-interaction \
  --optimize-autoloader \
  --prefer-dist

# ─── Step 3 · Node dependencies + frontend build ─────────────────────────────
if [[ "$SKIP_BUILD" == false ]]; then
  log "Installing Node dependencies..."
  npm ci --prefer-offline

  log "Building frontend assets (Vite)..."
  npm run build
  log "Frontend build complete → public/build/"
else
  warn "Skipping frontend build (--skip-build)"
fi

# ─── Step 4 · Environment hardening ──────────────────────────────────────────
log "Verifying production environment settings..."

# Ensure APP_ENV=production and APP_DEBUG=false
if grep -q "^APP_ENV=local" "$APP_DIR/.env"; then
  warn ".env has APP_ENV=local — setting to production"
  sed -i 's/^APP_ENV=local/APP_ENV=production/' "$APP_DIR/.env"
fi
if grep -q "^APP_DEBUG=true" "$APP_DIR/.env"; then
  warn ".env has APP_DEBUG=true — setting to false"
  sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' "$APP_DIR/.env"
fi

# ─── Step 5 · SQLite database ────────────────────────────────────────────────
log "Ensuring SQLite database file exists..."
DB_FILE="$APP_DIR/database/database.sqlite"
if [[ ! -f "$DB_FILE" ]]; then
  touch "$DB_FILE"
  log "Created $DB_FILE"
fi

# ─── Step 6 · Migrations ─────────────────────────────────────────────────────
if [[ "$SKIP_MIGRATE" == false ]]; then
  log "Running database migrations..."
  php artisan migrate --force
else
  warn "Skipping migrations (--skip-migrate)"
fi

# ─── Step 7 · Laravel optimisation cache ─────────────────────────────────────
log "Clearing and rebuilding Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
log "Caches rebuilt."

# Clear application cache (tide/wind data) so next request fetches fresh data
php artisan cache:clear
log "Application cache cleared."

# ─── Step 8 · Storage permissions ────────────────────────────────────────────
log "Setting storage and cache permissions..."
mkdir -p "$APP_DIR/storage/framework/cache/data"
mkdir -p "$APP_DIR/storage/framework/sessions"
mkdir -p "$APP_DIR/storage/framework/views"
mkdir -p "$APP_DIR/storage/logs"
mkdir -p "$APP_DIR/bootstrap/cache"

# Use www-data if nginx/php-fpm run as that user
WEB_USER="www-data"
if id "$WEB_USER" &>/dev/null; then
  chown -R "$WEB_USER:$WEB_USER" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
  chown "$WEB_USER:$WEB_USER" "$DB_FILE"
  chmod 664 "$DB_FILE"
  chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
  log "Permissions set for $WEB_USER."
else
  warn "User $WEB_USER not found. Set permissions manually."
fi

# ─── Step 9 · Laravel scheduler cron ─────────────────────────────────────────
CRON_JOB="* * * * * $WEB_USER php $APP_DIR/artisan schedule:run >> /dev/null 2>&1"
CRON_FILE="/etc/cron.d/masbocas"

if [[ ! -f "$CRON_FILE" ]]; then
  log "Installing cron entry for Laravel scheduler..."
  echo "$CRON_JOB" > "$CRON_FILE"
  chmod 644 "$CRON_FILE"
  log "Cron installed: $CRON_FILE"
else
  log "Cron file already exists: $CRON_FILE"
fi

# ─── Step 10 · Restart services ──────────────────────────────────────────────
log "Restarting PHP-FPM..."
if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
  systemctl restart "$PHP_FPM_SERVICE"
  log "$PHP_FPM_SERVICE restarted."
else
  warn "$PHP_FPM_SERVICE is not running. Start it manually: systemctl start $PHP_FPM_SERVICE"
fi

log "Reloading nginx..."
if systemctl is-active --quiet "$NGINX_SERVICE"; then
  nginx -t && systemctl reload "$NGINX_SERVICE"
  log "$NGINX_SERVICE reloaded."
else
  warn "$NGINX_SERVICE is not running. Start it manually: systemctl start $NGINX_SERVICE"
fi

# ─── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo "============================================================"
echo "  Deploy complete — $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Log: $LOG_FILE"
echo "============================================================"
echo ""
