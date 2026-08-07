#!/usr/bin/env bash
# ------------------------------------------------------------------------------
# restore-db.sh — Restore database SQLite dari backup secara atomik
#
# Cara pakai:
#   bash scripts/restore-db.sh backups/assets_20260807_120000.sqlite
#
# Urutan operasi (aman dan dapat-di-revert):
#   1. Validasi sumber backup (integrity_check) SEBELUM menyentuh DB aktif
#   2. Buat safety backup dari DB aktif via VACUUM INTO
#   3. Salin sumber ke file temp di direktori yang sama (same-filesystem)
#   4. Validasi file temp (integrity_check)
#   5. Baru sekarang: hapus WAL/SHM lama, mv temp → DB (satu operasi atomik)
#   6. Jika GAGAL di langkah manapun setelah step 2: DB lama otomatis dipulihkan
#
# PENTING: Hentikan web server sebelum menjalankan restore.
# ------------------------------------------------------------------------------

set -euo pipefail

if [ $# -lt 1 ]; then
    echo "Cara pakai: bash scripts/restore-db.sh <file_backup.sqlite>"
    echo ""
    echo "File backup yang tersedia:"
    ls -lh backups/ 2>/dev/null || echo "  (folder backups/ kosong)"
    exit 1
fi

BACKUP_FILE="$1"
DB_FILE="writable/assets.sqlite"
DB_DIR="$(dirname "$DB_FILE")"
DB_SHM="${DB_FILE}-shm"
DB_WAL="${DB_FILE}-wal"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
TMP_FILE="${DB_DIR}/assets_restore_tmp_${TIMESTAMP}.sqlite"

SAFETY_BACKUP=""
RESTORE_NEEDED=false

# Trap global: bersihkan temp file dan pulihkan DB jika terjadi error
cleanup_on_exit() {
    local exit_code=$?
    # Hapus temp file jika masih ada (artinya mv belum berhasil)
    rm -f "$TMP_FILE" 2>/dev/null || true
    # Pulihkan DB lama dari safety backup jika restore gagal
    if [ "$RESTORE_NEEDED" = true ] && [ -n "$SAFETY_BACKUP" ] && [ -f "$SAFETY_BACKUP" ]; then
        echo ""
        echo "⚡ Kegagalan terdeteksi — memulihkan database lama dari safety backup..."
        cp "$SAFETY_BACKUP" "$DB_FILE"
        echo "   ✅ Database lama dipulihkan: $DB_FILE"
    fi
    exit "$exit_code"
}
trap cleanup_on_exit EXIT

# ── 1. Periksa keberadaan file backup ─────────────────────────────────────────
if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: File backup tidak ditemukan: $BACKUP_FILE"
    exit 1
fi

# ── 2. Validasi header SQLite ─────────────────────────────────────────────────
MAGIC=$(head -c 6 "$BACKUP_FILE" 2>/dev/null || true)
if [[ "$MAGIC" != "SQLite" ]]; then
    echo "ERROR: File bukan database SQLite yang valid: $BACKUP_FILE"
    exit 1
fi

echo "⚠️  PASTIKAN web server sudah dihentikan sebelum restore!"
echo "   Sumber  : $BACKUP_FILE"
echo "   Target  : $DB_FILE"
echo ""

# ── 3. Validasi integritas sumber SEBELUM menyentuh DB aktif ─────────────────
echo "🔍 [1/5] Memvalidasi sumber backup..."
php -r "
\$src = '$BACKUP_FILE';
try {
    \$pdo = new PDO('sqlite:' . \$src);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$result = \$pdo->query('PRAGMA integrity_check')->fetchColumn();
    if (\$result !== 'ok') {
        fwrite(STDERR, 'ERROR: Backup tidak lulus integrity_check: ' . \$result . PHP_EOL);
        exit(1);
    }
    \$assets    = \$pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
    \$employees = \$pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
    \$endpoints = \$pdo->query('SELECT COUNT(*) FROM endpoints')->fetchColumn();
    echo '   ✅ Sumber valid — ' . \$assets . ' aset, '
         . \$employees . ' karyawan, ' . \$endpoints . ' endpoint' . PHP_EOL;
} catch (Exception \$e) {
    fwrite(STDERR, 'ERROR validasi sumber: ' . \$e->getMessage() . PHP_EOL);
    exit(1);
}
" || { echo "DIBATALKAN: Sumber tidak valid. DB aktif tidak diubah."; exit 1; }

# ── 4. Safety backup DB aktif via VACUUM INTO (menangkap state WAL) ──────────
echo "🛡️  [2/5] Backup DB aktif sebagai safety net..."
if [ -f "$DB_FILE" ]; then
    mkdir -p backups
    SAFETY_BACKUP="backups/assets_before_restore_${TIMESTAMP}.sqlite"
    php -r "
\$src  = '$DB_FILE';
\$dest = '$SAFETY_BACKUP';
try {
    \$pdo = new PDO('sqlite:' . \$src);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->exec('VACUUM INTO ' . \$pdo->quote(\$dest));
    echo '   ✅ Safety backup: ' . \$dest . PHP_EOL;
} catch (Exception \$e) {
    // Fallback: cp — jika DB rusak/tidak bisa dibuka lewat PDO
    copy(\$src, \$dest);
    echo '   ✅ Safety backup (cp fallback): ' . \$dest . PHP_EOL;
}
"
    # Dari sini, restore-on-failure aktif
    RESTORE_NEEDED=true
else
    echo "   (Tidak ada DB aktif — tidak perlu safety backup)"
fi

# ── 5. Salin sumber ke file temp (same-filesystem agar mv atomik) ─────────────
echo "📦 [3/5] Menyalin sumber ke file sementara..."
cp "$BACKUP_FILE" "$TMP_FILE"

# ── 6. Validasi file temp ─────────────────────────────────────────────────────
echo "🔍 [4/5] Memvalidasi file sementara..."
php -r "
\$tmp = '$TMP_FILE';
try {
    \$pdo = new PDO('sqlite:' . \$tmp);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
    \$result = \$pdo->query('PRAGMA integrity_check')->fetchColumn();
    if (\$result !== 'ok') {
        fwrite(STDERR, 'ERROR: File sementara tidak valid: ' . \$result . PHP_EOL);
        exit(1);
    }
    echo '   ✅ File sementara valid' . PHP_EOL;
} catch (Exception \$e) {
    fwrite(STDERR, 'ERROR validasi temp: ' . \$e->getMessage() . PHP_EOL);
    exit(1);
}
"

# ── 7. Hapus WAL/SHM LAMA, lalu mv atomik (langkah terakhir yang destruktif) ──
echo "🔄 [5/5] Menggantikan database aktif..."
# Hapus sidecar lama — hanya setelah temp sudah divalidasi
rm -f "$DB_WAL" "$DB_SHM"
# mv atomik (same-filesystem, tidak bisa parsial)
mv "$TMP_FILE" "$DB_FILE"

# mv berhasil — nonaktifkan restore-on-failure
RESTORE_NEEDED=false

# ── 8. Verifikasi akhir ───────────────────────────────────────────────────────
echo "🔍 Verifikasi akhir..."
php -r "
\$db = '$DB_FILE';
try {
    \$pdo = new PDO('sqlite:' . \$db);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$result = \$pdo->query('PRAGMA integrity_check')->fetchColumn();
    if (\$result !== 'ok') {
        fwrite(STDERR, 'ERROR integrity_check pasca-restore: ' . \$result . PHP_EOL);
        exit(1);
    }
    \$assets    = \$pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
    \$employees = \$pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
    \$endpoints = \$pdo->query('SELECT COUNT(*) FROM endpoints')->fetchColumn();
    echo '   ✅ Integrity check: OK' . PHP_EOL;
    echo '   📊 ' . \$assets . ' aset, ' . \$employees . ' karyawan, ' . \$endpoints . ' endpoint' . PHP_EOL;
} catch (Exception \$e) {
    fwrite(STDERR, 'ERROR verifikasi akhir: ' . \$e->getMessage() . PHP_EOL);
    exit(1);
}
" || {
    # Verifikasi akhir gagal — pulihkan dari safety backup secara manual
    # (RESTORE_NEEDED sudah false, jadi trap tidak akan menanganinya)
    echo "GAGAL: Verifikasi akhir gagal."
    if [ -n "$SAFETY_BACKUP" ] && [ -f "$SAFETY_BACKUP" ]; then
        echo "   Memulihkan DB lama dari safety backup..."
        cp "$SAFETY_BACKUP" "$DB_FILE"
        echo "   ✅ DB lama dipulihkan."
    fi
    exit 1
}

echo ""
echo "──────────────────────────────────────────"
echo "Restore berhasil : $(date)"
echo "Database aktif   : $DB_FILE"
ls -lh "$DB_FILE"
echo "──────────────────────────────────────────"
echo "Sekarang Anda bisa menjalankan kembali web server."
