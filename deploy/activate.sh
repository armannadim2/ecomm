#!/usr/bin/env bash
#
# Activates an unpacked release on Hostinger (staging or production).
#
#   deploy/activate.sh <app_dir> <release_dir>
#
# Layout (app_dir = e.g. ~/domains/totymes.com/staging-app):
#   releases/<timestamp>/   unpacked code (no vendor/, no .env)
#   shared/.env             settings and secrets (created once by hand, never in Git)
#   shared/storage/         uploads, logs, backups, sessions… (survives releases)
#   current -> releases/<timestamp>   what the web server serves (current/public)
#
# The switch of `current` is the last step, so visitors never see a half-installed release.
# The 5 newest releases are kept for a quick rollback: ln -sfn <older release> current.

set -euo pipefail

APP="${1:?app_dir missing}"
REL="${2:?release_dir missing}"
SHARED="$APP/shared"

[ -f "$SHARED/.env" ] || { echo "Missing $SHARED/.env: create it first (see docs/DEPLOY.md)." >&2; exit 1; }

mkdir -p "$SHARED/storage/app/public" "$SHARED/storage/app/private" "$SHARED/storage/app/backups" \
         "$SHARED/storage/framework/cache/data" "$SHARED/storage/framework/sessions" \
         "$SHARED/storage/framework/views" "$SHARED/storage/logs"

cd "$REL"

rm -rf storage
ln -s "$SHARED/storage" storage
ln -sfn "$SHARED/.env" .env
mkdir -p bootstrap/cache

composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Public uploads (product photos…) served from public/storage.
ln -sfn ../storage/app/public public/storage

php artisan migrate --force
php artisan optimize
php artisan filament:optimize

ln -sfn "$REL" "$APP/current"

# Workers started by the scheduler pick up the new code.
php artisan queue:restart

# Keep the 5 newest releases.
ls -1dt "$APP"/releases/*/ | tail -n +6 | xargs -r rm -rf

echo "Release $(basename "$REL") is live."
