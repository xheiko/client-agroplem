#!/bin/bash

set -e

REPO="/home/bitrix/www/local"
ARCHIVE_DIR="$REPO/archive"

cd "$REPO" || exit 1

# создаём архив (без попадания archive и git)
tar --exclude='./log' \
    --exclude='./archive' \
    --exclude='./.git' \
    -czf "$ARCHIVE_DIR/local_backup_$(date +%F).tar.gz" .

# оставляем только 10 последних архивов (по времени изменения)
ls -1t "$ARCHIVE_DIR"/local_backup_*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm -f

# git environment для cron
export PATH=/usr/bin:/bin:/usr/local/bin
export GIT_SSH_COMMAND="ssh -o BatchMode=yes"

# git операции
git add -A

if git diff --cached --quiet; then
  echo "[$(date)] No changes to commit"
  exit 0
fi

git commit -m "auto commit $(date '+%Y-%m-%d %H:%M:%S')"
git push origin main

echo "[$(date)] Backup + Git sync completed"
touch "$0"
