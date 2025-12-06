# Cara Memperbaiki Invalid API Key

## Masalah
HTTP Code 401 - "Invalid API key" berarti API key yang digunakan tidak valid atau sudah expired.

## Solusi

### 1. Buka File .env
File `.env` ada di root folder project: `D:\APLIKASI\laragon\www\cuaca\.env`

### 2. Pastikan API Key Benar
API key yang benar dari dashboard OpenWeatherMap Anda adalah:
```
4a8ea63a0dc8e6543e9ea4e81949c502
```

### 3. Edit File .env
Pastikan baris ini ada dan benar:
```env
OWM_API_KEY=4a8ea63a0dc8e6543e9ea4e81949c502
```

**PENTING:**
- ❌ JANGAN ada spasi sebelum/sesudah tanda `=`
- ❌ JANGAN pakai tanda kutip (`"` atau `'`)
- ✅ Harus persis seperti di atas

### 4. Restart Web Server
Setelah mengubah `.env`, **WAJIB restart**:
- **Laragon**: Klik "Stop All" lalu "Start All"
- **XAMPP**: Stop Apache, lalu Start lagi
- **PHP Built-in**: Stop (Ctrl+C) lalu jalankan lagi

### 5. Test Ulang
1. Refresh halaman `test-api.php`
2. Pastikan HTTP Code menjadi `200` (bukan `401`)
3. Jika masih `401`, cek lagi API key di `.env`

## Jika API Key Masih Tidak Valid

### Dapatkan API Key Baru:
1. Buka: https://openweathermap.org/api
2. Login ke akun Anda
3. Buka: https://home.openweathermap.org/api_keys
4. Copy API key yang **Active**
5. Paste ke file `.env`

### Atau Generate API Key Baru:
1. Di dashboard OpenWeatherMap
2. Klik "Create key"
3. Beri nama (misal: "Cuaca App")
4. Klik "Generate"
5. Copy API key baru
6. Paste ke file `.env`

## Verifikasi

Setelah memperbaiki, test lagi:
```
http://localhost/cuaca/test-api.php
```

Harus muncul:
- ✅ HTTP Code: 200
- ✅ Current Weather API BERHASIL
- ✅ Forecast API BERHASIL

