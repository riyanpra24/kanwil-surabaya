#!/usr/bin/env bash
# ------------------------------------------------------------------------------
# backup-db.sh — Backup database SQLite ke folder backups/
#
# Cara pakai:
#   bash scripts/backup-db.sh
#
# Script ini menggunakan PHP (PDO SQLite VACUUM INTO) untuk snapshot atomik —
# aman dijalankan meski aplikasi sedang berjalan, karena SQLite mengunci
# snapshot secara internal sehingga WAL di-flush sebelum disalin.
# Backup lama (>30 hari) otomatis dibersihkan.
# ------------------------------------------------------------------------------

set -euo pipefail

DB_FILE="writable/assets.sqlite"
BACKUP_DIR="backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_SQLITE="${BACKUP_DIR}/assets_${TIMESTAMP}.sqlite"

# Pastikan folder backups ada
mkdir -p "$BACKUP_DIR"

# Periksa apakah file database ada
if [ ! -f "$DB_FILE" ]; then
    echo "ERROR: File database tidak ditemukan: $DB_FILE"
    echo "Pastikan aplikasi pernah dijalankan sehingga database terbuat."
    exit 1
fi

echo "📦 Membuat backup atomik SQLite via VACUUM INTO..."

# Gunakan PHP PDO VACUUM INTO — snapshot atomik, flush WAL, aman saat app berjalan
php -r "
\$src  = '$DB_FILE';
\$dest = '$BACKUP_SQLITE';
try {
    \$pdo = new PDO('sqlite:' . \$src);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->exec('VACUUM INTO ' . \$pdo->quote(\$dest));
    echo '   ✅ Backup berhasil: ' . \$dest . PHP_EOL;
} catch (Exception \$e) {
    fwrite(STDERR, 'ERROR: ' . \$e->getMessage() . PHP_EOL);
    exit(1);
}
"

# Hapus backup lebih dari 30 hari
find "$BACKUP_DIR" -name "assets_*.sqlite" -mtime +30 -delete 2>/dev/null && \
    echo "   🗑️  Backup lama (>30 hari) dibersihkan." || true

# Tampilkan info file backup
echo ""
echo "════════════════════════════════════════"
echo "Backup selesai  : $(date)"
echo "Lokasi backup   : $BACKUP_DIR/"
ls -lh "$BACKUP_DIR/"
echo "════════════════════════════════════════"
echo ""
echo "Untuk restore backup ini:"
echo "  bash scripts/restore-db.sh $BACKUP_SQLITE"
