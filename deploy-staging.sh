#!/usr/bin/env bash
#
# deploy-staging.sh — deploy this Laravel app to the staging server over SSH.
#
# What it does:
#   1. rsync the project (minus the .deployignore list) into ~/public_html/staging
#      using the SSH key — incremental, with --delete to mirror the working tree.
#   2. Run the post-deploy steps on the server: composer install (--no-dev),
#      php artisan migrate --force, cache rebuild, queue:restart — wrapped in
#      maintenance mode (artisan down/up).
#
# Config lives in ./deploy.config (gitignored). Copy deploy.config.example to
# deploy.config and fill in the values, or override any var via the environment.
#
# Usage:
#   ./deploy-staging.sh              # full deploy (rsync + remote steps)
#   ./deploy-staging.sh --dry-run    # show what rsync WOULD transfer; no changes
#   ./deploy-staging.sh --files-only # rsync only; skip composer/migrate/caches
#   ./deploy-staging.sh --no-migrate # full deploy but skip php artisan migrate
#   ./deploy-staging.sh --help
#
set -euo pipefail

# --- locate ourselves so the script works from any CWD ---------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# --- defaults (override in deploy.config or via env) -----------------------
SSH_HOST="${SSH_HOST:-217.21.90.136}"
SSH_USER="${SSH_USER:-u684149649}"
SSH_PORT="${SSH_PORT:-65002}"
SSH_KEY="${SSH_KEY:-./id_skilltricks_new}"
# Remote path is relative to the SSH user's home (~).
REMOTE_PATH="${REMOTE_PATH:-public_html/staging}"
# Remote tool names — change if the host needs e.g. php8.2 / a composer.phar path.
REMOTE_PHP="${REMOTE_PHP:-php}"
REMOTE_COMPOSER="${REMOTE_COMPOSER:-composer}"

# Load local overrides if present (after defaults so the file wins).
if [[ -f "$SCRIPT_DIR/deploy.config" ]]; then
    # shellcheck disable=SC1091
    source "$SCRIPT_DIR/deploy.config"
fi

# --- flags -----------------------------------------------------------------
DRY_RUN=0
FILES_ONLY=0
DO_MIGRATE=1
for arg in "$@"; do
    case "$arg" in
        --dry-run)    DRY_RUN=1 ;;
        --files-only) FILES_ONLY=1 ;;
        --no-migrate) DO_MIGRATE=0 ;;
        -h|--help)
            # Print the leading comment block (header docs), stop at first code line.
            awk 'NR>1{ if(/^#/){ sub(/^# ?/,""); print } else exit }' "${BASH_SOURCE[0]}"
            exit 0 ;;
        *)
            echo "Unknown option: $arg (try --help)" >&2
            exit 2 ;;
    esac
done

# --- pretty logging --------------------------------------------------------
if [[ -t 1 ]]; then C_B="\033[1m"; C_G="\033[32m"; C_Y="\033[33m"; C_R="\033[31m"; C_0="\033[0m"; else C_B=""; C_G=""; C_Y=""; C_R=""; C_0=""; fi
log()  { echo -e "${C_B}==>${C_0} $*"; }
ok()   { echo -e "${C_G}  ✓${C_0} $*"; }
warn() { echo -e "${C_Y}  ! ${C_0}$*"; }
die()  { echo -e "${C_R}ERROR:${C_0} $*" >&2; exit 1; }

# --- preflight -------------------------------------------------------------
log "Preflight checks"
command -v rsync >/dev/null || die "rsync is not installed locally."
command -v ssh   >/dev/null || die "ssh is not installed locally."
[[ -f "$SSH_KEY" ]] || die "SSH key not found: $SSH_KEY"
[[ -f "$SCRIPT_DIR/.deployignore" ]] || die ".deployignore not found next to this script."
[[ -n "$SSH_HOST" && -n "$SSH_USER" ]] || die "SSH_HOST and SSH_USER must be set (deploy.config / env)."

# Private keys must be 0600 or ssh refuses to use them.
chmod 600 "$SSH_KEY" 2>/dev/null || true

SSH_OPTS=(-i "$SSH_KEY" -p "$SSH_PORT" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)
REMOTE="${SSH_USER}@${SSH_HOST}"
ok "Target: ${REMOTE}:~/${REMOTE_PATH}  (port ${SSH_PORT}, key ${SSH_KEY})"

# --- verify connectivity (skipped on dry-run so you can preview offline) ----
if [[ "$DRY_RUN" -eq 0 ]]; then
    log "Testing SSH connection"
    ssh "${SSH_OPTS[@]}" -o BatchMode=yes "$REMOTE" 'echo connected' >/dev/null \
        || die "Could not connect. Check host/port/key and that this machine's IP is allowed on the server."
    ok "SSH connection OK"
    # Ensure the remote directory exists before rsync writes into it.
    ssh "${SSH_OPTS[@]}" "$REMOTE" "mkdir -p \"\$HOME/${REMOTE_PATH}\""
fi

# --- rsync -----------------------------------------------------------------
# --stats / --itemize-changes work on both stock macOS rsync (2.6.x) and modern 3.x.
# NOTE: we deliberately do NOT use --chmod here — stock macOS rsync (2.6.x)
# rejects the D/F class prefixes. Web-safe perms are normalized over SSH after
# the transfer instead (see "Normalizing remote permissions" below). Without
# that, -a preserves the local dir mode (often 700 on macOS) and LiteSpeed
# returns 403 because it cannot traverse the document root.
RSYNC_OPTS=(-az --delete --stats --itemize-changes
            --exclude-from="$SCRIPT_DIR/.deployignore"
            -e "ssh ${SSH_OPTS[*]}")
if [[ "$DRY_RUN" -eq 1 ]]; then
    RSYNC_OPTS+=(--dry-run)
    log "DRY RUN — rsync will only report what it would do"
fi

log "Syncing files → ${REMOTE}:~/${REMOTE_PATH}/"
rsync "${RSYNC_OPTS[@]}" "$SCRIPT_DIR/" "${REMOTE}:${REMOTE_PATH}/"
ok "rsync complete"

if [[ "$DRY_RUN" -eq 1 ]]; then
    warn "Dry run finished — no files changed and no remote steps ran."
    exit 0
fi

# Normalize remote permissions so LiteSpeed can serve the tree: directories 755,
# files 644. Done over SSH (not via rsync --chmod) for old-rsync compatibility.
log "Normalizing remote permissions (dirs 755, files 644)"
ssh "${SSH_OPTS[@]}" "$REMOTE" \
    "cd \"\$HOME/${REMOTE_PATH}\" && find . -type d -exec chmod 755 {} + && find . -type f -exec chmod 644 {} +"
ok "Permissions normalized"

# --- post-deploy steps on the server --------------------------------------
if [[ "$FILES_ONLY" -eq 1 ]]; then
    warn "--files-only: skipping composer/migrate/caches. Run them manually if needed."
    log  "Done."
    exit 0
fi

log "Running remote deploy steps (composer, migrate, caches)"
# Pass config as positional args; remote script is single-quoted so nothing
# expands locally. The EXIT trap guarantees the app comes back up even on error.
ssh "${SSH_OPTS[@]}" "$REMOTE" 'bash -s' -- \
    "$REMOTE_PATH" "$REMOTE_PHP" "$REMOTE_COMPOSER" "$DO_MIGRATE" <<'REMOTE_SCRIPT'
set -euo pipefail
APP_DIR="$1"; PHP="$2"; COMPOSER="$3"; DO_MIGRATE="$4"

cd "$HOME/$APP_DIR" || { echo "ERROR: $HOME/$APP_DIR not found on server"; exit 1; }

# storage/ and bootstrap/cache are excluded from rsync (server-owned). Make sure
# the skeleton exists so artisan can boot on a first-time deploy.
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public bootstrap/cache

# Always lift maintenance mode on exit (success or failure).
trap '$PHP artisan up >/dev/null 2>&1 || true' EXIT

[ -f .env ] || echo "WARNING: .env not found on server — the app will not boot until you create it."

echo ">> Maintenance mode ON"
$PHP artisan down --render="errors::503" || true

echo ">> composer install --no-dev --optimize-autoloader"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ "$DO_MIGRATE" = "1" ]; then
    echo ">> php artisan migrate --force"
    $PHP artisan migrate --force
else
    echo ">> migrate SKIPPED (--no-migrate)"
fi

echo ">> php artisan storage:link"
$PHP artisan storage:link 2>/dev/null || true

echo ">> Rebuilding caches (config:cache intentionally skipped — see docs/DEPLOYMENT.md)"
$PHP artisan route:clear  || true
$PHP artisan view:clear   || true
$PHP artisan config:clear || true
$PHP artisan route:cache
$PHP artisan view:cache

echo ">> php artisan queue:restart"
$PHP artisan queue:restart || true

echo ">> Remote steps complete; lifting maintenance mode."
REMOTE_SCRIPT

ok "Remote deploy steps complete"
log "${C_G}Deploy finished.${C_0} Visit: https://staging.skilltricksinc.com"
