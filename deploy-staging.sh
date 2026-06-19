#!/usr/bin/env bash
#
# deploy-staging.sh — deploy this Laravel app to the staging server over SSH.
#
# What it does:
#   1. SSH into ~/public_html/staging and reset that Git checkout to origin/main.
#   2. Run the post-deploy steps on the server: composer install (--no-dev),
#      php artisan migrate --force, cache rebuild, queue:restart — wrapped in
#      maintenance mode (artisan down/up).
#
# Config lives in ./deploy.config (gitignored). Copy deploy.config.example to
# deploy.config and fill in the values, or override any var via the environment.
#
# Usage:
#   ./deploy-staging.sh              # full deploy (git reset + remote steps)
#   ./deploy-staging.sh --dry-run    # show remote commits/status; no app changes
#   ./deploy-staging.sh --files-only # git reset only; skip composer/migrate/caches
#   ./deploy-staging.sh --no-migrate # full deploy but skip php artisan migrate
#   ./deploy-staging.sh --skip-git-check
#                                      # skip local clean/synced Git verification
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
SKIP_GIT_CHECK=0
for arg in "$@"; do
    case "$arg" in
        --dry-run)    DRY_RUN=1 ;;
        --files-only) FILES_ONLY=1 ;;
        --no-migrate) DO_MIGRATE=0 ;;
        --skip-git-check) SKIP_GIT_CHECK=1 ;;
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
command -v ssh   >/dev/null || die "ssh is not installed locally."
command -v git   >/dev/null || die "git is not installed locally."
[[ -f "$SSH_KEY" ]] || die "SSH key not found: $SSH_KEY"
[[ -n "$SSH_HOST" && -n "$SSH_USER" ]] || die "SSH_HOST and SSH_USER must be set (deploy.config / env)."

# Private keys must be 0600 or ssh refuses to use them.
chmod 600 "$SSH_KEY" 2>/dev/null || true

SSH_OPTS=(-i "$SSH_KEY" -p "$SSH_PORT" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=20)
REMOTE="${SSH_USER}@${SSH_HOST}"
ok "Target: ${REMOTE}:~/${REMOTE_PATH}  (port ${SSH_PORT}, key ${SSH_KEY})"

if [[ "$SKIP_GIT_CHECK" -eq 0 ]]; then
    log "Verifying local Git state"
    git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "This deploy must be run from a Git checkout."

    current_branch="$(git branch --show-current)"
    [[ "$current_branch" == "main" ]] || die "Current branch is '$current_branch'; switch to main before deploying."

    git diff --quiet || die "Working tree has uncommitted changes. Commit/stash them, or use --skip-git-check deliberately."
    git diff --cached --quiet || die "Index has staged changes. Commit/stash them, or use --skip-git-check deliberately."

    if [[ -n "$(git ls-files --others --exclude-standard)" ]]; then
        die "Working tree has untracked files. Remove/ignore them, or use --skip-git-check deliberately."
    fi

    git fetch origin main
    local_head="$(git rev-parse HEAD)"
    origin_head="$(git rev-parse origin/main)"
    [[ "$local_head" == "$origin_head" ]] || die "Local main ($local_head) does not match origin/main ($origin_head). Pull/push first."
    ok "Local main matches origin/main at $local_head"
else
    warn "Skipping local Git clean/synced check; deploying remote origin/main."
fi

# --- remote deploy steps ----------------------------------------------------
log "Testing SSH connection"
ssh "${SSH_OPTS[@]}" -o BatchMode=yes "$REMOTE" 'echo connected' >/dev/null \
    || die "Could not connect. Check host/port/key and that this machine's IP is allowed on the server."
ok "SSH connection OK"

if [[ "$DRY_RUN" -eq 1 ]]; then
    log "DRY RUN — checking remote Git state only"
else
    log "Running remote deploy steps"
fi

# Pass config as positional args; remote script is single-quoted so nothing
# expands locally. The EXIT trap guarantees the app comes back up even on error.
ssh "${SSH_OPTS[@]}" "$REMOTE" 'bash -s' -- \
    "$REMOTE_PATH" "$REMOTE_PHP" "$REMOTE_COMPOSER" "$DO_MIGRATE" "$FILES_ONLY" "$DRY_RUN" <<'REMOTE_SCRIPT'
set -euo pipefail
APP_DIR="$1"; PHP="$2"; COMPOSER="$3"; DO_MIGRATE="$4"; FILES_ONLY="$5"; DRY_RUN="$6"

cd "$HOME/$APP_DIR" || { echo "ERROR: $HOME/$APP_DIR not found on server"; exit 1; }
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || { echo "ERROR: $PWD is not a Git checkout"; exit 1; }

echo ">> git fetch origin main"
git fetch origin main

CURRENT_HEAD="$(git rev-parse HEAD)"
TARGET_HEAD="$(git rev-parse origin/main)"

if [ "$DRY_RUN" = "1" ]; then
    echo ">> current HEAD: $CURRENT_HEAD"
    echo ">> origin/main:  $TARGET_HEAD"
    echo ">> ahead/behind: $(git rev-list --left-right --count HEAD...origin/main)"
    echo ">> status:"
    git status --short --branch
    echo ">> commits to deploy:"
    git log --oneline HEAD..origin/main || true
    exit 0
fi

echo ">> Maintenance mode ON"
$PHP artisan down --render="errors::503" || true

# Always lift maintenance mode on exit (success or failure).
trap '$PHP artisan up >/dev/null 2>&1 || true' EXIT

echo ">> git reset --hard origin/main"
git reset --hard origin/main
git checkout -B main origin/main
git branch --set-upstream-to=origin/main main >/dev/null 2>&1 || true

# Make sure server-owned runtime paths exist and secrets are not world-readable.
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public bootstrap/cache
{ [ ! -f .env ] || chmod 600 .env; }
{ [ ! -d storage/app ] || find storage/app -maxdepth 1 -type f -name '.*' -exec chmod 600 {} +; }

[ -f .env ] || echo "WARNING: .env not found on server — the app will not boot until you create it."

if [ "$FILES_ONLY" = "1" ]; then
    echo ">> --files-only: skipping composer/migrate/caches"
    exit 0
fi

echo ">> composer install --no-dev --optimize-autoloader"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ "$DO_MIGRATE" = "1" ]; then
    echo ">> php artisan migrate --force"
    $PHP artisan migrate --force
else
    echo ">> migrate SKIPPED (--no-migrate)"
fi

echo ">> php artisan storage:link"
if [ -L public/storage ]; then
    echo "   public/storage symlink already present"
elif $PHP artisan storage:link 2>/dev/null && [ -L public/storage ]; then
    echo "   public/storage created via artisan"
else
    # Some shared hosts (e.g. Hostinger) disable PHP's symlink(), so artisan
    # storage:link fails with "Call to undefined function symlink()". Fall back
    # to a relative shell symlink: public/storage -> ../storage/app/public.
    rm -f public/storage 2>/dev/null || true
    if ln -s ../storage/app/public public/storage; then
        echo "   public/storage created via shell ln -s (artisan symlink() unavailable)"
    else
        echo "   WARNING: could not create public/storage symlink; uploaded files may 404"
    fi
fi

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

if [[ "$DRY_RUN" -eq 1 ]]; then
    warn "Dry run finished — no app files changed and no remote deploy steps ran."
else
    ok "Remote deploy steps complete"
    log "${C_G}Deploy finished.${C_0} Visit: https://staging.skilltricksinc.com"
fi
