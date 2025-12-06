# 🔐 Panduan Setup Gmail App Password untuk SMTP

## Masalah yang Terjadi

Jika Anda mendapatkan error:
```
535-5.7.8 Username and Password not accepted
SMTP Error: Could not authenticate.
```

Ini berarti Gmail menolak kredensial Anda. **Gmail TIDAK menerima password biasa** untuk aplikasi eksternal. Anda **HARUS** menggunakan **App Password**.

---

## ✅ Solusi: Buat Gmail App Password

### Langkah 1: Aktifkan 2-Step Verification

1. Buka: https://myaccount.google.com/security
2. Scroll ke bagian **"2-Step Verification"**
3. Klik **"Get started"** atau **"Turn on"**
4. Ikuti langkah-langkah untuk mengaktifkan 2-Step Verification
   - Masukkan password Gmail Anda
   - Pilih metode verifikasi (SMS atau Google Authenticator)
   - Verifikasi dengan kode yang dikirim

### Langkah 2: Buat App Password

1. Setelah 2-Step Verification aktif, buka: https://myaccount.google.com/apppasswords
   - Atau: https://myaccount.google.com/security → Scroll ke **"App passwords"** → Klik **"App passwords"**

2. Jika diminta, masukkan password Gmail Anda lagi

3. Di halaman **"Select app"**, pilih:
   - **App**: Pilih **"Mail"** atau **"Other (Custom name)"**
   - **Device**: Pilih **"Other (Custom name)"** dan ketik: `Cuaca App` atau `SMTP`

4. Klik **"Generate"**

5. **Gmail akan menampilkan App Password** (16 karakter, tanpa spasi)
   - Contoh: `abcd efgh ijkl mnop`
   - **SALIN PASSWORD INI** (tanpa spasi): `abcdefghijklmnop`

### Langkah 3: Update File .env

Buka file `.env`` di root folder aplikasi, lalu update:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=abcdefghijklmnop
SMTP_FROM=your-email@gmail.com
SMTP_FROM_NAME=Cuaca & Aktivitas Harian
```

**PENTING:**
- `SMTP_USER`: Email Gmail Anda (contoh: `vanisaindriyani30@gmail.com`)
- `SMTP_PASS`: **App Password** yang baru saja dibuat (16 karakter, tanpa spasi)
- **JANGAN** gunakan password Gmail biasa!

### Langkah 4: Test Kembali

1. Buka: `http://localhost/cuaca/test-smtp.php`
2. Masukkan email Anda
3. Klik **"Kirim Test Email"**
4. Jika berhasil, Anda akan melihat pesan sukses dan email akan masuk ke inbox

---

## ❌ Troubleshooting

### Error: "App passwords" tidak muncul

**Penyebab:** 2-Step Verification belum aktif atau akun menggunakan Google Workspace.

**Solusi:**
1. Pastikan 2-Step Verification sudah aktif
2. Tunggu beberapa menit setelah mengaktifkan 2-Step Verification
3. Refresh halaman https://myaccount.google.com/apppasswords
4. Jika masih tidak muncul, coba logout dan login lagi ke Google Account

### Error: "Less secure app access"

**Penyebab:** Gmail sudah tidak mendukung "Less secure app access" sejak Mei 2022.

**Solusi:** **WAJIB** menggunakan App Password. Tidak ada alternatif lain.

### Error: Masih "Could not authenticate"

**Cek:**
1. ✅ Apakah 2-Step Verification sudah aktif?
2. ✅ Apakah App Password sudah dibuat?
3. ✅ Apakah `SMTP_PASS` di `.env` sudah diisi dengan App Password (16 karakter)?
4. ✅ Apakah tidak ada spasi di awal/akhir App Password?
5. ✅ Apakah `SMTP_USER` menggunakan email yang sama dengan yang membuat App Password?

### Error: "Connection timeout" atau "Could not connect"

**Penyebab:** Firewall atau koneksi internet.

**Solusi:**
1. Pastikan port 587 tidak diblokir firewall
2. Cek koneksi internet
3. Coba gunakan port 465 dengan `SMTPSecure = 'ssl'` (perlu update kode)

---

## 📝 Contoh Konfigurasi .env yang Benar

```env
# SMTP Configuration untuk Gmail
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=vanisaindriyani30@gmail.com
SMTP_PASS=abcdefghijklmnop
SMTP_FROM=vanisaindriyani30@gmail.com
SMTP_FROM_NAME=Cuaca & Aktivitas Harian
```

**Catatan:** Ganti `abcdefghijklmnop` dengan App Password yang sebenarnya!

---

## 🔗 Link Penting

- **App Passwords**: https://myaccount.google.com/apppasswords
- **2-Step Verification**: https://myaccount.google.com/security
- **Security Settings**: https://myaccount.google.com/security

---

## ✅ Checklist

Sebelum test, pastikan:

- [ ] 2-Step Verification sudah aktif
- [ ] App Password sudah dibuat (16 karakter)
- [ ] File `.env` sudah diupdate dengan App Password
- [ ] `SMTP_USER` menggunakan email yang benar
- [ ] `SMTP_PASS` menggunakan App Password (bukan password biasa)
- [ ] Tidak ada spasi di awal/akhir App Password
- [ ] File `.env` sudah disimpan

Setelah semua checklist terpenuhi, test lagi di `test-smtp.php`!

