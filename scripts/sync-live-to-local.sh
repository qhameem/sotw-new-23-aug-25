#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SYNC_ENV_FILE="${LIVE_SYNC_ENV_FILE:-$PROJECT_ROOT/.env.live-sync}"
LOCAL_ENV_FILE="${LOCAL_ENV_FILE:-$PROJECT_ROOT/.env}"
TMP_DIR="$PROJECT_ROOT/storage/app/sync-temp"
BACKUP_DIR="$PROJECT_ROOT/storage/app/sync-backups"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
REMOTE_DUMP_FILE="/tmp/live-sync-${TIMESTAMP}.sql"
LOCAL_DUMP_FILE="$TMP_DIR/live-${TIMESTAMP}.sql"

usage() {
    cat <<'EOF'
Usage:
  scripts/sync-live-to-local.sh [--yes]

What it does:
  1. Creates a backup of your local MySQL database.
  2. Creates a MySQL dump on the live server.
  3. Downloads that dump into this project.
  4. Wipes the local database and imports the live dump.
  5. Syncs product upload folders from live storage/app/public.

Required local files:
  .env
  .env.live-sync
EOF
}

log() {
    printf '[sync] %s\n' "$1"
}

fail() {
    printf '[sync] Error: %s\n' "$1" >&2
    exit 1
}

strip_wrapping_quotes() {
    local value="$1"

    if [[ "$value" == \"*\" && "$value" == *\" ]]; then
        value="${value:1:${#value}-2}"
    elif [[ "$value" == \'*\' && "$value" == *\' ]]; then
        value="${value:1:${#value}-2}"
    fi

    printf '%s' "$value"
}

env_get() {
    local file="$1"
    local key="$2"
    local line value

    line="$(grep -E "^${key}=" "$file" | tail -n 1 || true)"
    if [[ -z "$line" ]]; then
        return 1
    fi

    value="${line#*=}"
    strip_wrapping_quotes "$value"
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Required command not found: $1"
}

shell_quote() {
    printf "%q" "$1"
}

run_ssh() {
    local remote_script="$1"

    ssh -p "$LIVE_SYNC_SSH_PORT" "${LIVE_SYNC_SSH_USER}@${LIVE_SYNC_SSH_HOST}" \
        "bash -lc $(shell_quote "$remote_script")"
}

confirm_destructive_step() {
    local answer

    if [[ "${1:-}" == "--yes" ]]; then
        return 0
    fi

    printf 'This will replace your local database with the live database. Continue? [y/N] '
    read -r answer

    [[ "$answer" == "y" || "$answer" == "Y" ]]
}

[[ "${1:-}" == "--help" ]] && {
    usage
    exit 0
}

mkdir -p "$TMP_DIR" "$BACKUP_DIR"

require_command ssh
require_command rsync
require_command php
require_command mysql
require_command mysqldump

[[ -f "$LOCAL_ENV_FILE" ]] || fail "Local env file not found at $LOCAL_ENV_FILE"
[[ -f "$SYNC_ENV_FILE" ]] || fail "Sync env file not found at $SYNC_ENV_FILE"

set -a
# shellcheck disable=SC1090
source "$SYNC_ENV_FILE"
set +a

: "${LIVE_SYNC_SSH_HOST:?LIVE_SYNC_SSH_HOST is required}"
: "${LIVE_SYNC_SSH_USER:?LIVE_SYNC_SSH_USER is required}"
: "${LIVE_SYNC_SSH_PORT:=22}"
: "${LIVE_SYNC_REMOTE_STORAGE_ROOT:?LIVE_SYNC_REMOTE_STORAGE_ROOT is required}"
: "${LIVE_SYNC_REMOTE_DB_HOST:?LIVE_SYNC_REMOTE_DB_HOST is required}"
: "${LIVE_SYNC_REMOTE_DB_PORT:=3306}"
: "${LIVE_SYNC_REMOTE_DB_DATABASE:?LIVE_SYNC_REMOTE_DB_DATABASE is required}"
: "${LIVE_SYNC_REMOTE_DB_USERNAME:?LIVE_SYNC_REMOTE_DB_USERNAME is required}"
: "${LIVE_SYNC_REMOTE_DB_PASSWORD:?LIVE_SYNC_REMOTE_DB_PASSWORD is required}"
: "${LIVE_SYNC_MEDIA_DIRS:=logos product_media theme/branding}"
: "${LIVE_SYNC_USE_DELETE:=0}"

LOCAL_DB_CONNECTION="$(env_get "$LOCAL_ENV_FILE" DB_CONNECTION || true)"
LOCAL_DB_HOST="$(env_get "$LOCAL_ENV_FILE" DB_HOST || true)"
LOCAL_DB_PORT="$(env_get "$LOCAL_ENV_FILE" DB_PORT || true)"
LOCAL_DB_DATABASE="$(env_get "$LOCAL_ENV_FILE" DB_DATABASE || true)"
LOCAL_DB_USERNAME="$(env_get "$LOCAL_ENV_FILE" DB_USERNAME || true)"
LOCAL_DB_PASSWORD="$(env_get "$LOCAL_ENV_FILE" DB_PASSWORD || true)"

[[ "$LOCAL_DB_CONNECTION" == "mysql" ]] || fail "This script currently supports local MySQL only."
[[ -n "$LOCAL_DB_HOST" ]] || fail "DB_HOST is missing from $LOCAL_ENV_FILE"
[[ -n "$LOCAL_DB_PORT" ]] || fail "DB_PORT is missing from $LOCAL_ENV_FILE"
[[ -n "$LOCAL_DB_DATABASE" ]] || fail "DB_DATABASE is missing from $LOCAL_ENV_FILE"
[[ -n "$LOCAL_DB_USERNAME" ]] || fail "DB_USERNAME is missing from $LOCAL_ENV_FILE"

if ! confirm_destructive_step "${1:-}"; then
    fail "Cancelled."
fi

LOCAL_BACKUP_FILE="$BACKUP_DIR/local-${TIMESTAMP}.sql"

log "Creating local database backup at $LOCAL_BACKUP_FILE"
MYSQL_PWD="$LOCAL_DB_PASSWORD" mysqldump \
    --host="$LOCAL_DB_HOST" \
    --port="$LOCAL_DB_PORT" \
    --user="$LOCAL_DB_USERNAME" \
    --single-transaction \
    --quick \
    "$LOCAL_DB_DATABASE" > "$LOCAL_BACKUP_FILE"

log "Creating live database dump on the server"
REMOTE_DUMP_SCRIPT=$(cat <<EOF
set -euo pipefail
MYSQL_PWD=$(shell_quote "$LIVE_SYNC_REMOTE_DB_PASSWORD") mysqldump \
  --host=$(shell_quote "$LIVE_SYNC_REMOTE_DB_HOST") \
  --port=$(shell_quote "$LIVE_SYNC_REMOTE_DB_PORT") \
  --user=$(shell_quote "$LIVE_SYNC_REMOTE_DB_USERNAME") \
  --single-transaction \
  --quick \
  $(shell_quote "$LIVE_SYNC_REMOTE_DB_DATABASE") > $(shell_quote "$REMOTE_DUMP_FILE")
EOF
)
run_ssh "$REMOTE_DUMP_SCRIPT"

log "Downloading live database dump"
rsync -av -e "ssh -p $LIVE_SYNC_SSH_PORT" \
    "${LIVE_SYNC_SSH_USER}@${LIVE_SYNC_SSH_HOST}:${REMOTE_DUMP_FILE}" \
    "$LOCAL_DUMP_FILE"

log "Wiping local database tables"
(cd "$PROJECT_ROOT" && php artisan db:wipe --force)

log "Importing live database into local MySQL"
MYSQL_PWD="$LOCAL_DB_PASSWORD" mysql \
    --host="$LOCAL_DB_HOST" \
    --port="$LOCAL_DB_PORT" \
    --user="$LOCAL_DB_USERNAME" \
    "$LOCAL_DB_DATABASE" < "$LOCAL_DUMP_FILE"

RSYNC_FLAGS=(-av)
if [[ "$LIVE_SYNC_USE_DELETE" == "1" ]]; then
    RSYNC_FLAGS+=(--delete)
fi

for media_dir in $LIVE_SYNC_MEDIA_DIRS; do
    local_target="$PROJECT_ROOT/storage/app/public/$media_dir"
    mkdir -p "$local_target"

    log "Syncing $media_dir"
    rsync "${RSYNC_FLAGS[@]}" -e "ssh -p $LIVE_SYNC_SSH_PORT" \
        "${LIVE_SYNC_SSH_USER}@${LIVE_SYNC_SSH_HOST}:${LIVE_SYNC_REMOTE_STORAGE_ROOT}/${media_dir}/" \
        "$local_target/"
done

log "Cleaning up temp dump files"
rm -f "$LOCAL_DUMP_FILE"
run_ssh "rm -f $(shell_quote "$REMOTE_DUMP_FILE")"

log "Done. Local DB backup saved at $LOCAL_BACKUP_FILE"
