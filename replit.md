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
Langkah-langkah deployment ke shared hosting:

1. Upload **semua file** kecuali `.git/` dan `.codex-tmp/`
2. Arahkan **document root** ke folder `public/`
3. Pastikan folder `writable/` memiliki permission **775** atau **777**
4. Buat file `.env` di root project dengan isi:
   ```
   CI_ENVIRONMENT = production
   app.baseURL = https://domain-anda.com/
   encryption.key = hex2bin:GENERATE_DENGAN_php_spark_key_generate
   ADMIN_USERNAME = admin
   ADMIN_PASSWORD_HASH = $2y$10$...hash...
   ```
5. Generate encryption key: `php spark key:generate`
6. Generate password hash baru: `php -r "echo password_hash('password_baru', PASSWORD_BCRYPT);"`
7. Pastikan PHP 8.2+ dengan ekstensi: `pdo_sqlite`, `mbstring`, `intl`, `zip`

## Backup Database SQLite
Database SQLite (`writable/assets.sqlite`) berisi semua data live (aset, karyawan, endpoint).
**Wajib backup sebelum deploy ulang** agar data tidak tertimpa:

```bash
bash scripts/backup-db.sh
```

Backup disimpan di `writable/backups/` dengan nama berisi timestamp, dan otomatis dihapus setelah 30 hari.

> **Catatan:** File `assets.sqlite` sengaja di-track di git agar data awal tersedia saat deploy pertama ke server baru. Backup folder `writable/backups/` dikecualikan dari git.

## Struktur Penting
```
app/
  Config/         # Konfigurasi CI4 (Routes, Filters, Database, dll)
  Controllers/    # Auth, Dashboard, Assets, Employees, Endpoints
  Filters/        # AuthFilter (proteksi halaman login)
  Libraries/      # AssetRepository, EmployeeRepository, EndpointRepository, XlsxExporter
  Views/          # Halaman landing, dashboard, CRUD aset/karyawan/endpoint
public/           # Web root (index.php, CSS, JS, gambar)
system/           # CI4 framework core
vendor/           # Composer dependencies (termasuk laminas-escaper)
writable/
  assets.sqlite   # Database SQLite (aset, karyawan, endpoint)
  asset-dashboard-data.json  # Seed data aset
  employee-seed.json         # Seed data karyawan
  endpoint-seed.json         # Seed data endpoint
  session/        # Penyimpanan sesi PHP
  cache/          # Cache CI4
  logs/           # Log error CI4
```

## User Preferences
<!-- Record any user preferences here as you learn them -->
