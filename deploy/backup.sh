#!/usr/bin/env bash
# ===== QURBA: encrypted daily database backup + rotation + optional off-site copy =====
# Cron (as qurba):  30 2 * * * bash /var/www/qurba/deploy/backup.sh >> /var/backups/qurba/backup.log 2>&1
# Needs in /home/qurba/.qurba-backup.env:
#   BACKUP_PASSPHRASE=long-random-secret     (store a copy OFF the server)
#   RCLONE_REMOTE=b2:qurba-backups           (optional: any rclone remote = different provider)
set -euo pipefail
source /home/qurba/.qurba-backup.env
cd /var/www/qurba
DB=$(grep ^DB_DATABASE .env | cut -d= -f2); USER=$(grep ^DB_USERNAME .env | cut -d= -f2); PASS=$(grep ^DB_PASSWORD .env | cut -d= -f2-)
DIR=/var/backups/qurba; mkdir -p $DIR/daily $DIR/weekly $DIR/monthly
STAMP=$(date +%F); FILE="$DIR/daily/qurba-$STAMP.sql.gz.enc"

MYSQL_PWD="$PASS" mysqldump --single-transaction --routines --no-tablespaces -u "$USER" "$DB" \
  | gzip -9 | openssl enc -aes-256-cbc -pbkdf2 -salt -pass env:BACKUP_PASSPHRASE -out "$FILE"
# Verify the backup can be decrypted and unpacked
openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE -in "$FILE" | gunzip | head -c 200 | grep -q "MySQL dump" || { echo "BACKUP VERIFY FAILED"; exit 1; }

[ "$(date +%u)" = 7 ] && cp "$FILE" $DIR/weekly/
[ "$(date +%d)" = 01 ] && cp "$FILE" $DIR/monthly/
find $DIR/daily -name '*.enc' -mtime +7 -delete
find $DIR/weekly -name '*.enc' -mtime +35 -delete
find $DIR/monthly -name '*.enc' -mtime +190 -delete

if [ -n "${RCLONE_REMOTE:-}" ] && command -v rclone >/dev/null; then rclone sync $DIR "$RCLONE_REMOTE" --exclude backup.log; fi
echo "$(date -Is) backup OK $FILE"
