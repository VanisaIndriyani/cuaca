# Akun Login yang Sudah Terdaftar

Setelah import database `database.sql`, ada **2 akun** yang sudah siap digunakan:

## 🔐 Akun Admin

**Email:** `admin@cuaca.app`  
**Password:** `admin123`  
**Role:** `admin`

**Fitur yang bisa diakses:**
- ✅ Semua fitur user biasa
- ✅ Admin Panel
- ✅ Manajemen Users
- ✅ Manajemen Activities (semua user)
- ✅ Manajemen Notifications
- ✅ Lihat semua data

---

## 👤 Akun User

**Email:** `user@cuaca.app`  
**Password:** `admin123`  
**Role:** `user`

**Fitur yang bisa diakses:**
- ✅ Dashboard
- ✅ Cuaca
- ✅ Aktivitas (CRUD - hanya milik sendiri)
- ✅ Analitik
- ✅ Profile

---

## 📝 Cara Login

1. Buka aplikasi: `http://localhost/cuaca`
2. Klik "Masuk" atau langsung ke: `http://localhost/cuaca/auth/login.php`
3. Masukkan email dan password salah satu akun di atas
4. Klik "Masuk"

## ⚠️ Catatan Penting

- **Password default:** `admin123` (untuk kedua akun)
- **Password sudah di-hash** dengan bcrypt di database
- **Sangat disarankan** untuk mengganti password setelah login pertama kali
- Akun ini hanya untuk **testing/development**

## 🔄 Ganti Password

Setelah login, Anda bisa:
1. Klik profile di navbar
2. Atau akses: `http://localhost/cuaca/profile.php`
3. Update profile (untuk ganti password, perlu modifikasi tambahan)

## 🆕 Buat Akun Baru

Anda juga bisa membuat akun baru melalui:
- Halaman Registrasi: `http://localhost/cuaca/auth/register.php`
- Atau klik "Daftar" di halaman login

## 📊 Data Sample

Akun **user@cuaca.app** sudah memiliki:
- ✅ 5 aktivitas sample (Jogging, Kuliah, Futsal, dll)
- ✅ Data cuaca sample (Jakarta, Bandung, Surabaya)

---

## 🚨 Troubleshooting

### Tidak bisa login?

1. **Pastikan database sudah diimport:**
   - Cek di phpMyAdmin, tabel `users` harus ada 2 record

2. **Cek password:**
   - Pastikan mengetik: `admin123` (huruf kecil semua)
   - Tidak ada spasi di depan/belakang

3. **Cek email:**
   - Admin: `admin@cuaca.app`
   - User: `user@cuaca.app`

4. **Jika masih error:**
   - Cek error log PHP
   - Pastikan session berjalan
   - Clear browser cache

### Password tidak cocok?

Jika password tidak bekerja, coba reset dengan SQL:

```sql
-- Reset password admin ke: admin123
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@cuaca.app';

-- Reset password user ke: admin123
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'user@cuaca.app';
```

---

## 📋 Quick Reference

| Email | Password | Role | Akses |
|-------|----------|------|-------|
| admin@cuaca.app | admin123 | admin | Full access |
| user@cuaca.app | admin123 | user | Limited access |

---

**Selamat menggunakan aplikasi! 🎉**

