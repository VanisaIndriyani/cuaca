# Panduan Deployment ke cPanel

Panduan lengkap untuk deploy aplikasi Cuaca ke cPanel hosting.

## Persiapan Sebelum Deploy

### 1. Update Konfigurasi di File `.env`

Setelah upload ke cPanel, edit file `.env` di root folder aplikasi dan ubah konfigurasi berikut:

```env
# Database Configuration (sesuaikan dengan database di cPanel)
DB_HOST=localhost
DB_NAME=nama_database_anda
DB_USER=username_database_anda
DB_PASS=password_database_anda

# App Configuration (UBAH INI!)
APP_URL=https://domain-anda.com/cuaca
APP_NAME=Cuaca & Aktivitas Harian
TIMEZONE=Asia/Jakarta

# Google OAuth (UBAH INI!)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=https://domain-anda.com/cuaca/auth/google-callback.php
```

**PENTING:** 
- Ganti `https://domain-anda.com/cuaca` dengan URL production Anda
- Ganti `GOOGLE_REDIRECT_URI` dengan URL production callback Google OAuth

### 2. Update Google Cloud Console

1. Buka **Google Cloud Console**: https://console.cloud.google.com/
2. Pilih project Anda
3. Buka **APIs & Services** → **Credentials**
4. Klik pada OAuth 2.0 Client ID yang sudah dibuat
5. Di bagian **Authorized JavaScript origins**, tambahkan:
   ```
   https://domain-anda.com
   ```
6. Di bagian **Authorized redirect URIs**, tambahkan:
   ```
   https://domain-anda.com/cuaca/auth/google-callback.php
   ```
7. Klik **SAVE**
8. Tunggu beberapa menit agar perubahan diterapkan

### 3. Upload File ke cPanel

1. **Upload semua file** ke folder `public_html/cuaca/` (atau folder sesuai kebutuhan)
2. **Pastikan file `.env` sudah di-upload** dan sudah di-edit dengan konfigurasi production
3. **Pastikan folder `vendor/` sudah di-upload** (jika tidak, jalankan `composer install` di cPanel)

### 4. Setup Database di cPanel

1. Buka **cPanel** → **MySQL Databases**
2. Buat database baru (contoh: `cuaca_app`)
3. Buat user database baru dan berikan akses ke database tersebut
4. Import file `database.sql` ke database yang baru dibuat
5. Update konfigurasi database di file `.env`

### 5. Set Permission Folder

Pastikan folder berikut memiliki permission yang benar:
- `public/` → **755** (untuk upload avatar)
- `vendor/` → **755**
- `.env` → **644** (readable, tapi tidak writable dari web)

### 6. Install Composer Dependencies (jika perlu)

Jika folder `vendor/` belum di-upload, jalankan di terminal cPanel:

```bash
cd public_html/cuaca
composer install --no-dev --optimize-autoloader
```

## Checklist Deployment

- [ ] File `.env` sudah di-update dengan konfigurasi production
- [ ] `APP_URL` di `.env` sudah sesuai dengan domain production
- [ ] `GOOGLE_REDIRECT_URI` di `.env` sudah sesuai dengan domain production
- [ ] Google Cloud Console sudah di-update dengan redirect URI production
- [ ] Database sudah di-import dan konfigurasi di `.env` sudah benar
- [ ] Folder permission sudah benar
- [ ] Composer dependencies sudah terinstall
- [ ] Test login dengan Google OAuth berhasil

## Troubleshooting

### Error: "redirect_uri_mismatch"

**Penyebab:** Redirect URI di Google Cloud Console tidak sesuai dengan yang di `.env`

**Solusi:**
1. Pastikan `GOOGLE_REDIRECT_URI` di `.env` sama persis dengan yang di Google Cloud Console
2. Pastikan menggunakan `https://` (bukan `http://`) untuk production
3. Pastikan tidak ada trailing slash (`/`) di akhir URL
4. Tunggu beberapa menit setelah update di Google Cloud Console

### Error: "Database connection failed"

**Penyebab:** Konfigurasi database di `.env` salah

**Solusi:**
1. Pastikan `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` di `.env` sudah benar
2. Pastikan user database memiliki akses ke database
3. Pastikan database sudah di-import dengan benar

### Error: "Vendor autoload tidak ditemukan"

**Penyebab:** Folder `vendor/` belum ter-upload atau belum diinstall

**Solusi:**
1. Upload folder `vendor/` dari local ke cPanel, ATAU
2. Jalankan `composer install` di terminal cPanel

### Error: "File .env tidak ditemukan"

**Penyebab:** File `.env` belum di-upload atau tidak ada

**Solusi:**
1. Pastikan file `.env` sudah di-upload ke root folder aplikasi
2. Pastikan file `.env` tidak di-ignore oleh `.gitignore` (jika menggunakan Git)

## Keamanan Production

1. **Jangan commit file `.env`** ke Git (sudah di-ignore)
2. **Gunakan HTTPS** untuk production (SSL certificate)
3. **Set permission file `.env`** menjadi **644** (readable, tidak writable)
4. **Update OAuth consent screen** dari "Testing" ke "In Production" di Google Cloud Console
5. **Monitor error logs** di cPanel untuk debugging

## Catatan Penting

- **URL Production:** Pastikan semua URL menggunakan `https://` (bukan `http://`)
- **Google OAuth:** Pastikan redirect URI di Google Cloud Console sama persis dengan yang di `.env`
- **Database:** Pastikan database sudah di-import dan user memiliki akses yang benar
- **Composer:** Pastikan dependencies sudah terinstall di production

---

**Selamat!** Aplikasi Anda sudah siap digunakan di production! 🎉

