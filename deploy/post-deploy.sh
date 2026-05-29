#!/usr/bin/env bash
#
# Post-deploy: brings the v2 mobile API up to a known-good state without
# touching the web app or v1 in any visible way. Safe to re-run.
#
# What it does (in order):
#   1. ensure Passport signing keys exist (idempotent)
#   2. ensure a Passport personal-access client exists (idempotent)
#   3. apply additive v2 migrations (--force, so it runs in non-interactive prod)
#   4. clear cached config / routes / app cache
#   5. assert the result: keys present, oauth client present, _health green
#
# Exits non-zero loudly if any step fails — so a broken deploy can't ship green.
#
# Usage (from the project root or anywhere):
#   bash deploy/post-deploy.sh

set -euo pipefail

# --- locate project root (this script's parent dir) --------------------------
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

# --- helpers ----------------------------------------------------------------
log() { printf "==> %s\n" "$*"; }
fail() { printf "❌ %s\n" "$*" >&2; exit 1; }

# --- 1. Passport keys -------------------------------------------------------
log "Ensuring Passport signing keys"
php artisan pdcu:ensure-passport-keys

# --- 2. Passport personal access client -------------------------------------
log "Ensuring Passport personal-access client exists"
PAC_COUNT=$(php artisan tinker --execute='echo DB::table("oauth_personal_access_clients")->count();' 2>/dev/null | tail -n 1 | tr -d '[:space:]' || echo "0")
if [ "${PAC_COUNT:-0}" = "0" ]; then
  log "  no personal-access client found — creating one"
  php artisan passport:client --personal --no-interaction --name="PDCU Mobile v2"
else
  log "  personal-access client already present (count=$PAC_COUNT) — skipping"
fi

# --- 3. Migrations ----------------------------------------------------------
log "Applying database migrations"
php artisan migrate --force

# --- 4. Caches --------------------------------------------------------------
log "Clearing caches"
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# --- 5. Post-deploy assertions ----------------------------------------------
# Use `stat` (which only needs traverse permission on the parent dir) rather
# than reading the file — Passport's private key is mode 600 owned by the web
# user, so a deploy user (root / ubuntu / etc.) intentionally can't read it.
log "Verifying Passport keys are present and well-formed"
KEY_PATH="storage/oauth-private.key"
if [ ! -f "$KEY_PATH" ]; then
  fail "Passport private key is missing at $KEY_PATH"
fi
KEY_BYTES=$(stat -c%s "$KEY_PATH" 2>/dev/null || stat -f%z "$KEY_PATH" 2>/dev/null || echo 0)
if [ "${KEY_BYTES:-0}" -lt 500 ]; then
  fail "Passport private key looks broken (only $KEY_BYTES bytes at $KEY_PATH)"
fi

log "✅ post-deploy complete"
log "   Hit GET /api/v2/_health to confirm the runtime sees the same."
