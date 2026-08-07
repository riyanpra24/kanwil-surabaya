#!/bin/bash
# Backup script untuk database SQLite Jamkrindo
# Jalankan sebelum deploy ulang untuk menjaga data terbaru.
#
# Cara pakai:
#   bash scripts/backup-db.sh
#
# File backup akan disimpan di writable/backups/ dengan nama berisi timestamp.

set -e

SOURCE="writable/assets.sqlite"
BACKUP_DIR="writable/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DEST="${BACKUP_DIR}/assets_${TIMESTAMP}.sqlite"

if [ ! -f "$SOURCE" ]; then
    echo "File database tidak ditemukan: $SOURCE"
    exit 1
fi

mkdir -p "$BACKUP_DIR"
cp "$SOURCE" "$DEST"
echo "Backup berhasil: $DEST"

# Hapus backup lebih dari 30 hari
find "$BACKUP_DIR" -name "assets_*.sqlite" -mtime +30 -delete 2>/dev/null && \
    echo "Backup lama (>30 hari) dihapus." || true
