#!/usr/bin/env bash
# ------------------------------------------------------------------------------
# migrate-untrack-db.sh — Migrasi satu kali: melepas assets.sqlite dari Git
#
# Jalankan script ini SEKALI untuk pertama kali menarik update yang menghapus
# writable/assets.sqlite dari tracking Git.
#
# Cara pakai (dari direktori root project):
#   bash scripts/migrate-untrack-db.sh
#
# Script ini aman untuk dijalankan berulang kali (idempoten).
# ------------------------------------------------------------------------------

set -euo pipefail

DB_FILE="writable/assets.sqlite"
BACKUP_DIR="backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
MIGRATION_BACKUP="${BACKUP_DIR}/assets_migration_${TIMESTAMP}.sqlite"

echo "========================================"
echo " Migrasi: Melepas assets.sqlite dari Git"
echo "========================================"
echo ""

mkdir -p "$BACKUP_DIR"

# ── Langkah 1: Backup database live sebelum menyentuh Git ────────────────────
if [ -f "$DB_FILE" ]; then
    echo "Langkah 1/4 — Backup database live..."
    php -r "
\$src  = '$DB_FILE';
\$dest = '$MIGRATION_BACKUP';
try {
    \$pdo = new PDO('sqlite:' . \$src);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->exec('VACUUM INTO ' . \$pdo->quote(\$dest));
    echo '   ✅ Backup migration: ' . \$dest . PHP_EOL;
} catch (Exception \$e) {
    fwrite(STDERR, 'ERROR backup: ' . \$e->getMessage() . PHP_EOL);
    exit(1);
}
"
else
    echo "Langkah 1/4 — Tidak ada database untuk di-backup."
    MIGRATION_BACKUP=""
fi

# ── Langkah 2: Lepas assets.sqlite dari index Git (tanpa menghapus file) ─────
echo ""
echo "Langkah 2/4 — Melepas assets.sqlite dari tracking Git..."

if git ls-files --error-unmatch "$DB_FILE" &>/dev/null 2>&1; then
    # File masih tracked — lepaskan dari index agar git pull tidak gagal
    # karena file berubah
    git rm --cached "$DB_FILE"
    echo "   ✅ assets.sqlite dilepas dari index Git (file tetap ada di disk)"
else
    echo "   ✅ assets.sqlite sudah tidak di-track Git (tidak perlu tindakan)"
fi

# ── Langkah 3: git pull ───────────────────────────────────────────────────────
echo ""
echo "Langkah 3/4 — Menjalankan git pull..."
git pull
echo "   ✅ git pull selesai"

# Pastikan database masih ada setelah pull (seharusnya tetap ada karena sudah
# di-untrack sebelum pull, tapi pulihkan dari backup jika tidak ada)
if [ ! -f "$DB_FILE" ] && [ -n "$MIGRATION_BACKUP" ] && [ -f "$MIGRATION_BACKUP" ]; then
    echo "   ⚠️  Database hilang setelah pull. Memulihkan dari backup..."
    cp "$MIGRATION_BACKUP" "$DB_FILE"
    echo "   ✅ Database dipulihkan: $DB_FILE"
fi

# ── Langkah 4: Install post-merge hook ───────────────────────────────────────
echo ""
echo "Langkah 4/4 — Menginstall post-merge hook..."
HOOK_SRC="scripts/hooks/post-merge"
HOOK_DST=".git/hooks/post-merge"
if [ -f "$HOOK_SRC" ] && [ -d ".git/hooks" ]; then
    cp "$HOOK_SRC" "$HOOK_DST"
    chmod +x "$HOOK_DST"
    echo "   ✅ Post-merge hook diinstall: $HOOK_DST"
else
    echo "   ⚠️  Hook template tidak ditemukan — lewati."
fi

echo ""
echo "========================================"
echo " Migrasi selesai: $(date)"
echo ""
[ -f "$DB_FILE" ] && ls -lh "$DB_FILE"
echo ""
echo " Mulai sekarang:"
echo " - assets.sqlite TIDAK lagi di-track Git"
echo " - Post-merge hook aktif: database dipulihkan otomatis bila terhapus git"
echo " - Gunakan: bash scripts/backup-db.sh    — untuk backup rutin"
echo " - Gunakan: bash scripts/restore-db.sh   — untuk restore dari backup"
echo "========================================"
