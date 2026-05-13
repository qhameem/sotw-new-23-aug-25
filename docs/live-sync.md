# Live To Local Sync

## What this does

- Pulls the live MySQL database into local
- Syncs uploaded product files from live `storage/app/public`
- Creates a local database backup before replacing anything
- Shows live `rsync` progress for the SQL download and media file sync

## Files added

- `scripts/sync-live-to-local.sh`
- `.env.live-sync.example`

## Setup

1. Copy `.env.live-sync.example` to `.env.live-sync`
2. Fill in:
   - SSH host, user, and port
   - live database credentials
   - absolute live path to `storage/app/public`
3. Make the script executable:

```bash
chmod +x scripts/sync-live-to-local.sh
```

## Run

```bash
scripts/sync-live-to-local.sh
```

Skip the confirmation prompt:

```bash
scripts/sync-live-to-local.sh --yes
```

## Notes

- This script expects your local Laravel app to use MySQL.
- The live dump creation step runs first on the server, so it does not have a true percentage progress display.
- The SQL download shows a live percentage because it is one file.
- The media sync shows live per-file progress, not one single overall percentage for all folders combined.
- By default it syncs:
  - `logos`
  - `product_media`
  - `theme/branding`
- If you want local folders to exactly match live, set `LIVE_SYNC_USE_DELETE=1` in `.env.live-sync`.
