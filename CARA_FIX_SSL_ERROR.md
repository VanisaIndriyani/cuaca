# 🔧 Cara Fix SSL Certificate Error di Hosting

## Error yang Terjadi

```
Peer certificate CN=`bekantan.kencang.com' did not match expected CN=`smtp.gmail.com'
```

Error ini masih muncul berarti ada 2 kemungkinan:
1. **File belum di-update** di hosting (belum di-pull dari GitHub)
2. **Konfigurasi `.env` belum ditambahkan** `SMTP_DISABLE_SSL_VERIFY=true`

---

## ✅ Langkah-langkah Fix

### Langkah 1: Pull File Terbaru dari GitHub

Pastikan file `app/Services/EmailService.php` sudah di-update di hosting:

**Via cPanel File Manager:**
1. Login ke cPanel
2. Buka **File Manager**
3. Masuk ke folder `public_html/cuaca/`
4. **Hapus** file `app/Services/EmailService.php` yang lama
5. **Upload** file `app/Services/EmailService.php` yang baru dari local (atau pull dari GitHub)

**Via Git (jika tersedia):**
```bash
cd public_html/cuaca
git pull origin main
```

### Langkah 2: Update File `.env` di Hosting

**PENTING:** Tambahkan baris ini di file `.env` di hosting:

```env
SMTP_DISABLE_SSL_VERIFY=true
```

**File `.env` lengkapnya harus seperti ini:**

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=bitubimy_cuaca
DB_USER=bitubimy_izsaa
DB_PASS=jokiizsaa200504

# OpenWeatherMap API
OWM_API_KEY=4a8ea63a0dc8e6543e9ea4e81949c502

# App Configuration
APP_URL=https://bitubi.my.id/cuaca
APP_NAME=Cuaca & Aktivitas Harian
TIMEZONE=Asia/Jakarta

# Google OAuth
GOOGLE_CLIENT_ID=1098409376359-ldqs1ql8oa2fb7bm8p5vf1237vodhjpe.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
GOOGLE_REDIRECT_URI=https://bitubi.my.id/cuaca/auth/google-callback.php

# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=mewarrahman@gmail.com
SMTP_PASS=cbucnvfmseizzpdv
SMTP_FROM=mewarrahman@gmail.com
SMTP_FROM_NAME=Cuaca & Aktivitas Harian

# Fix SSL Certificate Error untuk Shared Hosting
SMTP_DISABLE_SSL_VERIFY=true
```

**Catatan:**
- Pastikan tidak ada spasi di awal/akhir nilai
- Pastikan menggunakan `true` (huruf kecil, tanpa tanda kutip)
- Jangan lupa **simpan** file setelah edit

### Langkah 3: Verifikasi Konfigurasi

1. Buka: `https://bitubi.my.id/cuaca/test-smtp-hosting.php`
2. Scroll ke bagian **"2. Check .env Configuration"**
3. Pastikan `SMTP_DISABLE_SSL_VERIFY` muncul dan bernilai `true`

### Langkah 4: Test Kirim Email

1. Di halaman `test-smtp-hosting.php`
2. Masukkan email Anda
3. Klik **"Kirim Test Email"**
4. Cek apakah error sudah hilang

### Langkah 5: Cek Error Log

Jika masih error, cek:
- `logs/email_errors.log` - untuk melihat error terbaru
- PHP Error Log di cPanel - untuk melihat log sistem

Di error log, cari pesan:
- `EmailService SSL Config - Production: Yes, SMTP_DISABLE_SSL_VERIFY: true` ✅ (berarti konfigurasi terbaca)
- `EmailService SSL Config - Production: Yes, SMTP_DISABLE_SSL_VERIFY: false/not set` ❌ (berarti `.env` belum di-update)

---

## 🔍 Troubleshooting

### Masih Error Setelah Update?

**Cek 1: Apakah file sudah di-update?**
- Buka `app/Services/EmailService.php` di hosting
- Cari baris: `SMTP_DISABLE_SSL_VERIFY`
- Jika tidak ada, berarti file belum di-update

**Cek 2: Apakah `.env` sudah di-update?**
- Buka file `.env` di hosting
- Cari baris: `SMTP_DISABLE_SSL_VERIFY=true`
- Jika tidak ada, tambahkan baris tersebut

**Cek 3: Apakah konfigurasi terbaca?**
- Buka `test-smtp-hosting.php`
- Lihat bagian "2. Check .env Configuration"
- Pastikan `SMTP_DISABLE_SSL_VERIFY` muncul

**Cek 4: Apakah ada cache?**
- Beberapa hosting menggunakan opcache
- Coba restart PHP atau clear cache di cPanel

### Alternatif: Disable SSL Verification Secara Permanen

Jika masih tidak berfungsi, kita bisa modifikasi kode untuk **selalu disable SSL verification di production** tanpa perlu set di `.env`. Tapi ini kurang aman.

---

## ✅ Checklist

Sebelum test, pastikan:

- [ ] File `app/Services/EmailService.php` sudah di-update di hosting
- [ ] File `.env` sudah ditambahkan `SMTP_DISABLE_SSL_VERIFY=true`
- [ ] Tidak ada spasi di awal/akhir nilai di `.env`
- [ ] File `.env` sudah disimpan
- [ ] Test via `test-smtp-hosting.php`

---

## 📝 Quick Fix

**Copy-paste ini ke file `.env` di hosting:**

```env
SMTP_DISABLE_SSL_VERIFY=true
```

Pastikan baris ini ada di bagian bawah file `.env`, setelah konfigurasi SMTP lainnya.

---

## 🆘 Masih Error?

Jika masih error setelah semua langkah di atas:

1. **Screenshot error log** dari `logs/email_errors.log`
2. **Screenshot konfigurasi** dari `test-smtp-hosting.php`
3. **Pastikan** file `app/Services/EmailService.php` di hosting sudah sama dengan yang di GitHub

