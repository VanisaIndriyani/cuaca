# Cara Mendapatkan Google OAuth Credentials

Panduan lengkap untuk mendapatkan `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` dari Google Cloud Console.

## Langkah 1: Buka Google Cloud Console

1. Buka browser dan kunjungi: **https://console.cloud.google.com/**
2. Login dengan akun Google Anda

## Langkah 2: Buat Project Baru (atau Pilih Project yang Ada)

1. Di bagian atas, klik dropdown **"Select a project"**
2. Klik **"New Project"**
3. Isi:
   - **Project name**: `Cuaca App` (atau nama lain)
   - **Location**: Pilih organization (bisa skip)
4. Klik **"Create"**
5. Tunggu beberapa detik sampai project dibuat
6. Pastikan project yang baru dibuat sudah terpilih

## Langkah 3: Enable Google+ API

1. Di menu kiri, klik **"APIs & Services"** → **"Library"**
2. Cari **"Google+ API"** atau **"People API"**
3. Klik pada **"Google+ API"** atau **"People API"**
4. Klik tombol **"Enable"**
5. Tunggu sampai API diaktifkan

## Langkah 4: Buat OAuth 2.0 Credentials

1. Di menu kiri, klik **"APIs & Services"** → **"Credentials"**
2. Klik tombol **"+ CREATE CREDENTIALS"** di bagian atas
3. Pilih **"OAuth client ID"**

### Jika Muncul OAuth Consent Screen:

1. Pilih **"External"** (untuk testing)
2. Klik **"CREATE"**
3. Isi form:
   - **App name**: `Cuaca App` (atau nama lain)
   - **User support email**: Pilih email Anda
   - **Developer contact information**: Masukkan email Anda
4. Klik **"SAVE AND CONTINUE"**
5. Di bagian **"Scopes"**, klik **"SAVE AND CONTINUE"** (skip dulu)
6. Di bagian **"Test users"**, klik **"ADD USERS"** dan tambahkan email Google Anda
7. Klik **"SAVE AND CONTINUE"**
8. Klik **"BACK TO DASHBOARD"**

### Lanjutkan Membuat OAuth Client ID:

1. Klik **"+ CREATE CREDENTIALS"** lagi
2. Pilih **"OAuth client ID"**
3. Pilih **"Web application"** sebagai Application type
4. Isi:
   - **Name**: `Cuaca Web Client` (atau nama lain)
   - **Authorized JavaScript origins**: 
     ```
     http://localhost
     ```
   - **Authorized redirect URIs**: 
     ```
     http://localhost/cuaca/auth/google-callback.php
     ```
5. Klik **"CREATE"**

## Langkah 5: Copy Credentials

Setelah OAuth client dibuat, akan muncul popup dengan:
- **Your Client ID**: Copy nilai ini (contoh: `123456789-abcdefghijklmnop.apps.googleusercontent.com`)
- **Your Client Secret**: Copy nilai ini (contoh: `GOCSPX-abcdefghijklmnopqrstuvwxyz`)

**PENTING:** Simpan credentials ini dengan aman!

## Langkah 6: Tambahkan ke File .env

1. Buka file `.env` di root folder project
2. Tambahkan atau update baris berikut:

```env
# Google OAuth
GOOGLE_CLIENT_ID=123456789-abcdefghijklmnop.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwxyz
GOOGLE_REDIRECT_URI=http://localhost/cuaca/auth/google-callback.php
```

**Ganti dengan credentials yang Anda dapatkan!**

## Langkah 7: Test Login dengan Google

1. Buka aplikasi: `http://localhost/cuaca/auth/login.php`
2. Klik tombol **"Masuk dengan Google"**
3. Pilih akun Google yang sudah ditambahkan sebagai test user
4. Klik **"Allow"** untuk memberikan izin
5. Anda akan di-redirect kembali ke aplikasi dan sudah login

## Troubleshooting

### Error: "redirect_uri_mismatch"

**Penyebab:** Redirect URI di Google Cloud Console tidak sesuai dengan yang di `.env`

**Solusi:**
1. Buka Google Cloud Console → APIs & Services → Credentials
2. Klik pada OAuth 2.0 Client ID yang sudah dibuat
3. Pastikan **Authorized redirect URIs** berisi:
   ```
   http://localhost/cuaca/auth/google-callback.php
   ```
4. Klik **"SAVE"**
5. Tunggu beberapa menit (Google perlu waktu untuk update)
6. Coba lagi

### Error: "access_denied"

**Penyebab:** Email Anda belum ditambahkan sebagai test user

**Solusi:**
1. Buka Google Cloud Console → APIs & Services → OAuth consent screen
2. Scroll ke bagian **"Test users"**
3. Klik **"ADD USERS"**
4. Tambahkan email Google Anda
5. Klik **"SAVE"**
6. Coba login lagi

### Error: "invalid_client"

**Penyebab:** Client ID atau Client Secret salah

**Solusi:**
1. Pastikan di file `.env`:
   - Tidak ada spasi sebelum/sesudah `=`
   - Tidak ada tanda kutip (`"` atau `'`)
   - Client ID dan Secret sudah benar
2. Restart web server (Apache/Nginx)
3. Coba lagi

### Error: "Vendor autoload tidak ditemukan"

**Penyebab:** Composer dependencies belum diinstall

**Solusi:**
1. Buka terminal/command prompt
2. Masuk ke folder project:
   ```bash
   cd D:\APLIKASI\laragon\www\cuaca
   ```
3. Jalankan:
   ```bash
   composer install
   ```
4. Tunggu sampai selesai
5. Coba login lagi

### Error: "cURL error 77: error setting certificate file"

**Penyebab:** Masalah dengan sertifikat SSL di Windows/Laragon. cURL tidak dapat menemukan atau menggunakan file sertifikat CA.

**Solusi:**
Error ini sudah diperbaiki di kode dengan menonaktifkan verifikasi SSL untuk development lokal. Jika masih terjadi:

1. **Pastikan kode sudah di-update** - File `auth/google-callback.php` dan `auth/google-login.php` sudah dikonfigurasi untuk menangani masalah SSL.

2. **Untuk Production (lebih aman):**
   - Download CA certificate bundle dari: https://curl.se/ca/cacert.pem
   - Simpan file `cacert.pem` di folder project (misalnya: `config/cacert.pem`)
   - Update kode untuk menggunakan file tersebut:
     ```php
     $httpClient = new \GuzzleHttp\Client([
         'verify' => __DIR__ . '/../config/cacert.pem',
     ]);
     ```

3. **Alternatif untuk Local Development:**
   - Jika masih error, pastikan `guzzlehttp/guzzle` sudah terinstall:
     ```bash
     composer require guzzlehttp/guzzle
     ```

**Catatan:** Solusi saat ini menonaktifkan verifikasi SSL hanya untuk development lokal. Untuk production, gunakan solusi dengan file `cacert.pem`.

## Deployment ke Production (cPanel)

**PENTING:** Sebelum deploy ke production, lakukan langkah berikut:

### 1. Update File `.env`

Ubah konfigurasi berikut di file `.env`:

```env
# App Configuration
APP_URL=https://domain-anda.com/cuaca

# Google OAuth
GOOGLE_REDIRECT_URI=https://domain-anda.com/cuaca/auth/google-callback.php
```

**Ganti `https://domain-anda.com/cuaca` dengan URL production Anda!**

### 2. Update Google Cloud Console

1. Buka **Google Cloud Console** → **APIs & Services** → **Credentials**
2. Klik pada OAuth 2.0 Client ID yang sudah dibuat
3. Di bagian **Authorized JavaScript origins**, tambahkan:
   ```
   https://domain-anda.com
   ```
4. Di bagian **Authorized redirect URIs**, tambahkan:
   ```
   https://domain-anda.com/cuaca/auth/google-callback.php
   ```
5. Klik **SAVE**
6. Tunggu beberapa menit agar perubahan diterapkan

### 3. Update OAuth Consent Screen

1. Buka **Google Cloud Console** → **APIs & Services** → **OAuth consent screen**
2. Ubah dari **"Testing"** ke **"In Production"**
3. Pastikan semua informasi sudah lengkap
4. Klik **"PUBLISH APP"**

**Lihat file `DEPLOYMENT.md` untuk panduan lengkap deployment ke cPanel.**

## Catatan Penting

1. **Untuk Production:**
   - Setelah aplikasi siap di-deploy, ubah OAuth consent screen dari "Testing" ke "In Production"
   - Tambahkan domain production ke **Authorized JavaScript origins** dan **Authorized redirect URIs**
   - Update `GOOGLE_REDIRECT_URI` di `.env` sesuai domain production
   - **PENTING:** Pastikan menggunakan `https://` (bukan `http://`) untuk production

2. **Keamanan:**
   - Jangan share Client Secret ke public
   - Jangan commit file `.env` ke Git (sudah di-ignore)
   - Jika credentials ter-expose, segera buat credentials baru di Google Cloud Console

3. **Quota:**
   - Google OAuth free tier cukup untuk aplikasi kecil-menengah
   - Monitor usage di Google Cloud Console

## Link Penting

- **Google Cloud Console**: https://console.cloud.google.com/
- **OAuth 2.0 Documentation**: https://developers.google.com/identity/protocols/oauth2
- **Google+ API Documentation**: https://developers.google.com/+/web/api/rest

## Contoh File .env Lengkap

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=cuaca_app
DB_USER=root
DB_PASS=

# OpenWeatherMap API
OWM_API_KEY=your_openweather_api_key

# Google OAuth
GOOGLE_CLIENT_ID=123456789-abcdefghijklmnop.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwxyz
GOOGLE_REDIRECT_URI=http://localhost/cuaca/auth/google-callback.php

# App Configuration
APP_URL=http://localhost/cuaca
APP_NAME=Cuaca & Aktivitas Harian
TIMEZONE=Asia/Jakarta
```

---

**Selamat!** Login dengan Google sudah siap digunakan! 🎉

