# Panduan Instalasi Lengkap

## Persyaratan Sistem

- PHP >= 7.4
- MySQL/MariaDB >= 5.7
- Composer
- Web Server (Apache/Nginx) atau PHP Built-in Server
- OpenWeatherMap API Key (gratis di https://openweathermap.org/api)

## Langkah Instalasi

### 1. Clone atau Download Project

```bash
# Jika menggunakan git
git clone <repository-url>
cd cuaca

# Atau extract zip file ke folder cuaca
```

### 2. Install Dependencies dengan Composer

```bash
composer install
```

Ini akan menginstall:
- `google/apiclient` untuk Google OAuth
- `phpmailer/phpmailer` untuk email (optional)

### 3. Setup Database

#### A. Buat Database

Buka phpMyAdmin atau MySQL CLI:

```sql
CREATE DATABASE cuaca_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### B. Import Database Schema

**Via phpMyAdmin:**
1. Pilih database `cuaca_app`
2. Klik tab "Import"
3. Pilih file `database.sql`
4. Klik "Go"

**Via Command Line:**
```bash
mysql -u root -p cuaca_app < database.sql
```

### 4. Konfigurasi Environment

#### A. Copy File .env

```bash
# Windows (PowerShell)
Copy-Item .env.example .env

# Linux/Mac
cp .env.example .env
```

#### B. Edit File .env

Buka file `.env` dan isi dengan konfigurasi Anda:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=cuaca_app
DB_USER=root
DB_PASS=

# OpenWeatherMap API (WAJIB)
# Daftar di https://openweathermap.org/api untuk mendapatkan API key gratis
OWM_API_KEY=your_openweathermap_api_key_here

# Google OAuth (OPSIONAL - bisa dikosongkan jika tidak digunakan)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/cuaca/auth/google-callback.php

# VAPID Keys untuk Web Push (OPSIONAL)
# Generate dengan: npm install -g web-push && web-push generate-vapid-keys
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:admin@example.com

# App Configuration
APP_URL=http://localhost/cuaca
APP_NAME=Cuaca & Aktivitas Harian
TIMEZONE=Asia/Jakarta
```

### 5. Setup Web Server

#### Opsi A: Menggunakan Laragon (Recommended untuk Windows)

1. Letakkan folder `cuaca` di `C:\laragon\www\cuaca`
2. Start Laragon
3. Akses: `http://localhost/cuaca`

#### Opsi B: Menggunakan XAMPP

1. Letakkan folder `cuaca` di `C:\xampp\htdocs\cuaca`
2. Start Apache dan MySQL di XAMPP Control Panel
3. Akses: `http://localhost/cuaca`

#### Opsi C: PHP Built-in Server

```bash
php -S localhost:8000 -t .
```

Akses: `http://localhost:8000`

### 6. Setup OpenWeatherMap API Key

1. Daftar di https://openweathermap.org/api
2. Pilih plan "Free" (gratis)
3. Copy API key Anda
4. Paste ke file `.env` sebagai `OWM_API_KEY`

### 7. Setup Google OAuth (Optional)

Jika ingin menggunakan login dengan Google:

1. Buka https://console.cloud.google.com/
2. Buat project baru atau pilih project yang ada
3. Enable "Google+ API"
4. Buat OAuth 2.0 Client ID:
   - Application type: Web application
   - Authorized redirect URIs: `http://localhost/cuaca/auth/google-callback.php`
5. Copy Client ID dan Client Secret ke file `.env`

### 8. Setup Web Push (Optional)

Jika ingin menggunakan Web Push Notifications:

```bash
# Install web-push CLI
npm install -g web-push

# Generate VAPID keys
web-push generate-vapid-keys
```

Copy Public Key dan Private Key ke file `.env`

### 9. Set Permissions (Linux/Mac)

```bash
chmod -R 755 .
chmod -R 777 public/cache/
```

### 10. Test Aplikasi

1. Buka browser: `http://localhost/cuaca`
2. Login dengan akun demo:
   - **Admin**: admin@cuaca.app / admin123
   - **User**: user@cuaca.app / admin123

## Troubleshooting

### Error: "Connection Error" di Database

**Solusi:**
- Pastikan MySQL/MariaDB berjalan
- Periksa konfigurasi di `.env` (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- Pastikan database `cuaca_app` sudah dibuat

### Error: "OpenWeatherMap API Error"

**Solusi:**
- Pastikan API key sudah diisi di `.env`
- Periksa quota API key Anda (free tier: 60 calls/minute)
- Pastikan koneksi internet tersedia

### Error: "Class 'Google_Client' not found"

**Solusi:**
```bash
composer install
```
Pastikan folder `vendor/` sudah ada

### Error: "Permission denied" di public/cache/

**Solusi (Linux/Mac):**
```bash
chmod -R 777 public/cache/
```

### Halaman Blank / White Screen

**Solusi:**
- Aktifkan error reporting di `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```
- Periksa error log web server
- Pastikan semua file sudah ter-upload dengan benar

### CSS/JS tidak loading

**Solusi:**
- Pastikan path `base_url()` benar di file `.env` (APP_URL)
- Periksa file `.htaccess` sudah ada
- Clear browser cache

## Verifikasi Instalasi

Setelah instalasi, pastikan:

- ✅ Database terhubung (tidak ada error di halaman)
- ✅ Bisa login dengan akun demo
- ✅ Dashboard menampilkan cuaca (jika API key sudah diisi)
- ✅ Bisa menambah aktivitas
- ✅ Grafik muncul di halaman analitik

## Next Steps

Setelah instalasi berhasil:

1. Ganti password default untuk akun admin dan user
2. Setup Google OAuth (jika diperlukan)
3. Setup Web Push (jika diperlukan)
4. Customize tema dan tampilan sesuai kebutuhan
5. Deploy ke production server

## Support

Jika ada masalah, periksa:
- File log error PHP
- Console browser (F12)
- Network tab di browser untuk melihat request yang gagal

