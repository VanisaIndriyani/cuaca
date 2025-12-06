# Setup Forgot Password dengan Verifikasi Kode

Panduan lengkap untuk setup fitur forgot password dengan verifikasi kode via email.

## Langkah 1: Buat Tabel Database

Jalankan SQL berikut untuk membuat tabel `password_resets`:

```sql
-- Table: password_resets
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_code (code),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Atau import file `database_password_reset.sql` yang sudah disediakan.

## Langkah 2: Konfigurasi Email di `.env`

Tambahkan konfigurasi email di file `.env`:

```env
# Email Configuration (untuk reset password)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=your-email@gmail.com
SMTP_FROM_NAME=Cuaca & Aktivitas Harian
```

### Untuk Gmail:

1. **Aktifkan 2-Step Verification** di akun Google Anda
2. **Buat App Password**:
   - Buka: https://myaccount.google.com/apppasswords
   - Pilih "Mail" dan "Other (Custom name)"
   - Masukkan nama: "Cuaca App"
   - Copy password yang dihasilkan
   - Gunakan password ini sebagai `SMTP_PASS` di `.env`

**PENTING:** Jangan gunakan password Gmail biasa, gunakan App Password!

## Langkah 3: Test Fitur

1. Buka halaman login: `http://localhost/cuaca/auth/login.php`
2. Klik "Lupa Password?"
3. Masukkan email yang terdaftar
4. Cek email untuk mendapatkan kode 6 digit
5. Masukkan kode di halaman verifikasi
6. Set password baru

## Fitur yang Tersedia

### 1. Halaman Forgot Password (`auth/forgot-password.php`)
- Input email
- Generate kode 6 digit
- Kirim kode ke email
- Validasi email

### 2. Halaman Verifikasi Kode (`auth/verify-code.php`)
- Input kode 6 digit
- Verifikasi kode
- Kode berlaku 15 menit
- Auto-submit saat kode lengkap

### 3. Halaman Reset Password (`auth/reset-password.php`)
- Input password baru
- Konfirmasi password
- Validasi kekuatan password
- Update password di database

## Keamanan

1. **Kode berlaku 15 menit** - Kode otomatis expired setelah 15 menit
2. **Kode sekali pakai** - Setelah digunakan, kode tidak bisa digunakan lagi
3. **Rate limiting** - Kode lama dihapus saat request kode baru
4. **Email tidak diungkap** - Jika email tidak terdaftar, tetap menampilkan pesan sukses (security best practice)

## Troubleshooting

### Email tidak terkirim

**Penyebab:** Konfigurasi SMTP salah atau email service tidak tersedia

**Solusi:**
1. Pastikan `SMTP_USER` dan `SMTP_PASS` di `.env` sudah benar
2. Untuk Gmail, pastikan menggunakan App Password (bukan password biasa)
3. Pastikan 2-Step Verification sudah aktif
4. Cek error log PHP untuk detail error

### Kode tidak valid

**Penyebab:** 
- Kode sudah expired (lebih dari 15 menit)
- Kode sudah digunakan
- Kode salah

**Solusi:**
1. Request kode baru
2. Pastikan menggunakan kode yang paling baru
3. Cek email spam/junk folder

### Error: "Table password_resets doesn't exist"

**Penyebab:** Tabel belum dibuat

**Solusi:**
1. Jalankan SQL untuk membuat tabel `password_resets`
2. Atau import file `database_password_reset.sql`

## Catatan Penting

1. **Untuk Production:** 
   - Gunakan email service yang reliable (SendGrid, Mailgun, dll)
   - Atau gunakan PHPMailer dengan konfigurasi SMTP yang benar
   - Pastikan email tidak masuk ke spam

2. **Testing:**
   - Gunakan email testing seperti Mailtrap untuk development
   - Atau gunakan Gmail dengan App Password

3. **Security:**
   - Jangan expose kode di URL
   - Gunakan session untuk menyimpan email setelah verifikasi
   - Hapus kode yang sudah digunakan

---

**Selamat!** Fitur forgot password sudah siap digunakan! 🎉

