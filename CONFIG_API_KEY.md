# Cara Mengatur API Key OpenWeatherMap

## API Key Anda

Dari dashboard OpenWeatherMap, API key Anda adalah:
```
4a8ea63a0dc8e6543e9ea4e81949c502
```

## Langkah Setup

### 1. Buat File .env

Jika belum ada, buat file `.env` di root folder project (sama level dengan `composer.json`).

### 2. Copy Isi Berikut ke File .env

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=cuaca_app
DB_USER=root
DB_PASS=

# OpenWeatherMap API (WAJIB - Isi dengan API key Anda)
OWM_API_KEY=4a8ea63a0dc8e6543e9ea4e81949c502

# Google OAuth (OPSIONAL - bisa dikosongkan)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/cuaca/auth/google-callback.php

# VAPID Keys for Web Push (OPSIONAL)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:admin@example.com

# App Configuration
APP_URL=http://localhost/cuaca
APP_NAME=Cuaca & Aktivitas Harian
TIMEZONE=Asia/Jakarta
```

### 3. Pastikan Baris Ini Terisi

```env
OWM_API_KEY=4a8ea63a0dc8e6543e9ea4e81949c502
```

**PENTING:**
- Jangan ada spasi sebelum atau sesudah tanda `=`
- Jangan ada tanda kutip (`"` atau `'`)
- Pastikan tidak ada karakter tersembunyi

### 4. Simpan File

Setelah mengisi, simpan file `.env`

### 5. Test

1. Buka aplikasi: `http://localhost/cuaca`
2. Login dengan akun demo
3. Coba cari cuaca di dashboard
4. Jika muncul data cuaca, berarti API key sudah benar!

## Troubleshooting

### Jika Cuaca Tidak Muncul

1. **Cek file .env:**
   - Pastikan `OWM_API_KEY` sudah diisi
   - Pastikan tidak ada typo

2. **Cek format:**
   - Harus: `OWM_API_KEY=4a8ea63a0dc8e6543e9ea4e81949c502`
   - Jangan: `OWM_API_KEY="4a8ea63a0dc8e6543e9ea4e81949c502"`
   - Jangan: `OWM_API_KEY = 4a8ea63a0dc8e6543e9ea4e81949c502`

3. **Restart web server:**
   - Jika menggunakan Laragon, restart Apache
   - Jika menggunakan XAMPP, restart Apache

4. **Cek error log:**
   - Buka browser console (F12)
   - Lihat apakah ada error API

### Jika Masih Error

- Pastikan API key masih **Active** di dashboard OpenWeatherMap
- Cek quota API (free tier: 60 calls/minute)
- Pastikan koneksi internet tersedia

## Lokasi File .env

File `.env` harus berada di:
```
cuaca/
├── .env          ← File ini harus ada di sini
├── composer.json
├── database.sql
└── ...
```

## Catatan

- API key ini bersifat **pribadi**, jangan share ke public
- File `.env` sudah di-ignore oleh git (aman)
- Jika API key expired atau limit habis, generate key baru di dashboard OpenWeatherMap

