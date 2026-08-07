# Sistem Manajemen Aset & SDM — Jamkrindo

## Overview
Aplikasi web internal berbasis **CodeIgniter 4** untuk manajemen aset IT, data karyawan, dan monitoring endpoint jaringan kantor Jamkrindo Kanwil Jawa Timur.

## Stack
- **Language:** PHP 8.2
- **Framework:** CodeIgniter 4.7.x
- **Database:** SQLite 3 (file: `writable/assets.sqlite`) — diakses via PDO langsung
- **Web root:** `public/`
- **Dependencies:** Composer (`vendor/` sudah ada, tidak perlu install ulang)

## Fitur Utama
- Login dengan satu akun admin (kredensial dibaca dari environment variable `ADMIN_USERNAME` dan `ADMIN_PASSWORD_HASH`)
- Dashboard ringkasan aset & SDM
- CRUD Data Aset IT
- CRUD Data Karyawan per unit/cabang
- CRUD & Monitoring Endpoint (komputer/laptop) per unit
- Export monitoring endpoint ke Excel (.xlsx)

## Menjalankan Aplikasi (Replit)
Workflow **"Start application"** sudah dikonfigurasi:
```
php -S 0.0.0.0:5000 -t public
```

## Konfigurasi Environment
- `CI_ENVIRONMENT` — sudah diset ke `production` via env var Replit
- `app.baseURL` — otomatis terdeteksi dari request (lihat `app/Config/App.php`)
- Database: tidak perlu konfigurasi MySQL; app menggunakan SQLite langsung
- `ADMIN_USERNAME` — username login admin (simpan sebagai Replit Secret)
- `ADMIN_PASSWORD_HASH` — bcrypt hash dari password admin (simpan sebagai Replit Secret)
- `APP_ENCRYPTION_KEY` — encryption key CI4 format `hex2bin:...` (simpan sebagai Replit Secret). Generate dengan: `php spark key:generate --show`

### Cara Mengganti Password Admin

1. Generate bcrypt hash dari password baru menggunakan perintah berikut di terminal:
   ```bash
   php -r "echo password_hash('PASSWORD_BARU_ANDA', PASSWORD_BCRYPT, ['cost' => 10]);"
   ```
   Ganti `PASSWORD_BARU_ANDA` dengan password yang diinginkan.

2. Salin output hash (dimulai dengan `$2y$10$...`) dan simpan sebagai nilai `ADMIN_PASSWORD_HASH` di Replit Secrets.

3. Simpan username baru sebagai nilai `ADMIN_USERNAME` di Replit Secrets.

> **Catatan keamanan:** Gunakan password minimal 16 karakter yang terdiri dari kombinasi huruf besar, huruf kecil, angka, dan simbol. Jangan pernah simpan password plaintext — hanya hash bcrypt-nya saja.


## Deploy ke Hosting (Reserved VM / Apache/cPanel)

### ⚠️ Penting: Database SQLite Tidak Ada di Git

File `writable/assets.sqlite` **tidak di-commit ke Git** (ada di `.gitignore`). Hanya seed JSON yang ada di Git sebagai data awal. Saat pindah server atau `git pull` besar, database harus di-upload/restore manual.

---

### Migrasi Satu Kali: Melepas assets.sqlite dari Git

Jika Anda sedang pada checkout **lama** (sebelum update ini) dan `assets.sqlite` masih di-track Git, lakukan langkah berikut **dari terminal** sebelum atau sebagai pengganti `git pull` biasa:

**Opsi A — Pakai script (jika script sudah tersedia di checkout Anda):**
```bash
bash scripts/migrate-untrack-db.sh
```
Script ini otomatis: backup DB → lepas dari index Git (`git rm --cached`) → `git pull` → pulihkan DB jika terhapus → install post-merge hook.

**Opsi B — Perintah manual (jika script belum ada di checkout lama):**
```bash
# 1. Backup database live (atomik via PHP VACUUM INTO)
mkdir -p backups
php -r "
\$ts = date('Ymd_His');
\$pdo = new PDO('sqlite:writable/assets.sqlite');
\$pdo->exec('VACUUM INTO ' . \$pdo->quote(\"backups/assets_migration_{\$ts}.sqlite\"));
echo 'Backup: backups/assets_migration_' . \$ts . '.sqlite' . PHP_EOL;
"

# 2. Lepaskan dari index Git agar pull tidak gagal karena file berubah
git rm --cached writable/assets.sqlite

# 3. Tarik update
git pull

# 4. Verifikasi DB masih ada (seharusnya tetap ada)
ls -lh writable/assets.sqlite 2>/dev/null \
  || echo "DB hilang — restore dengan: bash scripts/restore-db.sh backups/<file>.sqlite"
```

---

### Backup Rutin Sebelum Deploy

```bash
bash scripts/backup-db.sh
```

Membuat snapshot atomik via `VACUUM INTO` ke `backups/` (aman saat aplikasi berjalan). Backup lama (>30 hari) dibersihkan otomatis.

---

### Deploy ke Shared Hosting Baru

1. **Backup database** terlebih dahulu:
   ```bash
   bash scripts/backup-db.sh
   ```

2. Upload **semua file** kecuali `.git/`, `.codex-tmp/`, dan `backups/`

3. Arahkan **document root** ke folder `public/`

4. Pastikan folder `writable/` memiliki permission **775** atau **777**

5. Buat file `.env` di root project:
   ```
   CI_ENVIRONMENT = production
   app.baseURL = https://domain-anda.com/
   encryption.key = hex2bin:GENERATE_DENGAN_php_spark_key_generate
   ADMIN_USERNAME = admin
   ADMIN_PASSWORD_HASH = $2y$10$...hash...
   ```

6. Generate encryption key: `php spark key:generate`

7. Generate password hash: `php -r "echo password_hash('password_baru', PASSWORD_BCRYPT);"`

8. **Upload database** ke `writable/assets.sqlite`:
   - Via cPanel File Manager: upload file backup `.sqlite` ke `writable/assets.sqlite`
   - Via SSH: `bash scripts/restore-db.sh backups/assets_YYYYMMDD_HHMMSS.sqlite`

9. Pastikan PHP 8.2+ dengan ekstensi: `pdo_sqlite`, `mbstring`, `intl`, `zip`

---

### Restore Database

> ⚠️ Hentikan web server sebelum restore. Script memvalidasi backup sumber, menyalin ke file temp, memvalidasi temp, lalu `mv` atomik. Jika gagal di tahap manapun, database lama otomatis dipulihkan.

```bash
bash scripts/restore-db.sh backups/assets_YYYYMMDD_HHMMSS.sqlite
```

---

### Seed Data (Database Kosong / Server Baru)

Jika tidak ada backup, seed JSON tersedia di:
- `writable/asset-dashboard-data.json` — ringkasan aset
- `writable/employee-seed.json` — data karyawan awal
- `writable/endpoint-seed.json` — data endpoint awal

File-file ini di-commit ke Git sehingga selalu tersedia di server baru.

## Struktur Penting
```
app/
  Config/         # Konfigurasi CI4 (Routes, Filters, Database, dll)
  Controllers/    # Auth, Dashboard, Assets, Employees, Endpoints
  Filters/        # AuthFilter (proteksi halaman login)
  Libraries/      # AssetRepository, EmployeeRepository, EndpointRepository, XlsxExporter
  Views/          # Halaman landing, dashboard, CRUD aset/karyawan/endpoint
public/           # Web root (index.php, CSS, JS, gambar)
scripts/
  backup-db.sh    # Backup database SQLite (VACUUM INTO, atomik)
  restore-db.sh   # Restore database SQLite dengan WAL cleanup & integrity check
system/           # CI4 framework core
vendor/           # Composer dependencies (termasuk laminas-escaper)
writable/
  assets.sqlite          # Database SQLite (aset, karyawan, endpoint) — TIDAK di Git
  asset-dashboard-data.json  # Seed data aset — di Git
  employee-seed.json         # Seed data karyawan — di Git
  endpoint-seed.json         # Seed data endpoint — di Git
  session/        # Penyimpanan sesi PHP
  cache/          # Cache CI4
  logs/           # Log error CI4
backups/          # Backup database (TIDAK di Git)
```

## User Preferences
<!-- Record any user preferences here as you learn them -->
