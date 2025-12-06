# Quick Setup Guide

## Setup Cepat (5 Menit)

### 1. Install Composer Dependencies
```bash
composer install
```

### 2. Import Database
- Buka phpMyAdmin
- Buat database: `cuaca_app`
- Import file: `database.sql`

### 3. Setup .env
```bash
# Copy file
cp .env.example .env

# Edit .env, minimal isi:
DB_HOST=localhost
DB_NAME=cuaca_app
DB_USER=root
DB_PASS=
OWM_API_KEY=your_api_key_here
APP_URL=http://localhost/cuaca
```

### 4. Akses Aplikasi
```
http://localhost/cuaca
```

### 5. Login
- Email: admin@cuaca.app
- Password: admin123

## Dapatkan OpenWeatherMap API Key

1. Kunjungi: https://openweathermap.org/api
2. Klik "Sign Up" (gratis)
3. Verifikasi email
4. Copy API key dari dashboard
5. Paste ke file `.env` sebagai `OWM_API_KEY`

## Struktur File Penting

```
cuaca/
├── .env                    # Konfigurasi (WAJIB diisi)
├── database.sql            # Import database ini
├── config/                 # File konfigurasi
├── app/                    # Models & Services
├── auth/                   # Login/Register
├── dashboard.php           # Halaman utama
└── README.md               # Dokumentasi lengkap
```

## Checklist Instalasi

- [ ] Composer install selesai
- [ ] Database diimport
- [ ] File .env sudah dibuat dan diisi
- [ ] OpenWeatherMap API key sudah diisi
- [ ] Bisa akses http://localhost/cuaca
- [ ] Bisa login dengan akun demo

## Masalah Umum

**Database error?**
→ Pastikan MySQL berjalan dan database sudah dibuat

**API error?**
→ Pastikan OWM_API_KEY sudah diisi di .env

**Halaman blank?**
→ Periksa error log, pastikan semua file ada

**CSS tidak muncul?**
→ Periksa APP_URL di .env sesuai dengan URL Anda

