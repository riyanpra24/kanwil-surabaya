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
- Login dengan satu akun admin (kredensial di `app/Controllers/Auth.php`)
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

## Deploy ke Hosting (Apache/cPanel)
Langkah-langkah deployment ke shared hosting:

1. Upload **semua file** kecuali `.git/` dan `.codex-tmp/`
2. Arahkan **document root** ke folder `public/`
3. Pastikan folder `writable/` memiliki permission **775** atau **777**
4. Buat file `.env` di root project dengan isi:
   ```
   CI_ENVIRONMENT = production
   app.baseURL = https://domain-anda.com/
   encryption.key = hex2bin:GENERATE_DENGAN_php_spark_key_generate
   ```
5. Generate encryption key dengan perintah:
   ```
   php spark key:generate
   ```
   Lalu masukkan hasilnya ke `.env`
6. Pastikan PHP 8.2+ dengan ekstensi: `pdo_sqlite`, `mbstring`, `intl`, `zip`

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
