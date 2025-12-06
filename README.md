# Aplikasi Cuaca dan Aktivitas Harian

Aplikasi web untuk monitoring cuaca dan manajemen aktivitas harian dengan fitur notifikasi dan analitik.

## Teknologi

- **Backend**: PHP Native (tanpa framework MVC)
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, Chart.js
- **Libraries**: Google OAuth, PHPMailer (optional), JWT (optional)

## Fitur

### Autentikasi
- ✅ Login/Logout/Registrasi
- ✅ Password hashing dengan `password_hash()`
- ✅ Google OAuth 2.0

### Cuaca
- ✅ Input/pilih lokasi
- ✅ Cuaca saat ini (suhu, kondisi, kelembapan, angin, dll)
- ✅ Prakiraan cuaca 5 hari ke depan
- ✅ Integrasi OpenWeatherMap API dengan caching
- ✅ Deteksi lokasi otomatis (geolocation)

### Aktivitas Harian
- ✅ CRUD lengkap untuk aktivitas
- ✅ Kategori aktivitas (olahraga, pendidikan, kerja, istirahat, lainnya)
- ✅ Filter berdasarkan tanggal dan kategori
- ✅ Lihat aktivitas per hari

### Grafik
- ✅ Grafik tren suhu (time-series)
- ✅ Grafik tren kelembapan (time-series)
- ✅ Grafik aktivitas per kategori (bar chart)
- ✅ Grafik aktivitas per kategori (pie chart)

### Notifikasi
- ✅ Web Push Notifications (Service Worker)
- ✅ Log notifikasi ke database

### Analitik
- ✅ Rata-rata suhu minggu ini
- ✅ Rata-rata kelembapan
- ✅ Rekomendasi aktivitas berdasarkan cuaca
- ✅ Export laporan CSV

### Admin Panel
- ✅ Dashboard admin
- ✅ Manajemen users
- ✅ Manajemen activities
- ✅ Manajemen notifications

## Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd cuaca
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup Database

1. Buat database MySQL:
```sql
CREATE DATABASE cuaca_app;
```

2. Import database:
```bash
mysql -u root -p cuaca_app < database.sql
```

Atau melalui phpMyAdmin:
- Buka phpMyAdmin
- Pilih database `cuaca_app`
- Import file `database.sql`

### 4. Konfigurasi Environment

1. Copy file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```

2. Edit file `.env` dan isi dengan konfigurasi Anda:

```env
# Database
DB_HOST=localhost
DB_NAME=cuaca_app
DB_USER=root
DB_PASS=

# OpenWeatherMap API
OWM_API_KEY=your_api_key_here

# Google OAuth (optional)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost/cuaca/auth/google-callback.php

# VAPID Keys untuk Web Push (optional)
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:admin@example.com

# App Configuration
APP_URL=http://localhost/cuaca
APP_NAME=Cuaca & Aktivitas Harian
TIMEZONE=Asia/Jakarta
```

### 5. Setup Web Server

#### Menggunakan Laragon/XAMPP:

1. Letakkan folder `cuaca` di `laragon/www/` atau `xampp/htdocs/`
2. Akses melalui browser: `http://localhost/cuaca`

#### Menggunakan PHP Built-in Server:

```bash
php -S localhost:8000 -t .
```

Akses: `http://localhost:8000`

### 6. Generate VAPID Keys (untuk Web Push)

Jika ingin menggunakan Web Push, generate VAPID keys:

```bash
# Install web-push CLI
npm install -g web-push

# Generate keys
web-push generate-vapid-keys
```

Copy public key dan private key ke file `.env`

## Akun Demo

Setelah import database, gunakan akun berikut:

### Admin
- **Email**: admin@cuaca.app
- **Password**: admin123

### User
- **Email**: user@cuaca.app
- **Password**: admin123

> **Catatan**: Password default adalah `admin123` (hashed dengan bcrypt)

## Struktur Direktori

```
cuaca/
├── app/
│   ├── Models/          # Model classes (User, Activity, WeatherData, Notification)
│   └── Services/        # Service classes (ApiClientWeather, AnalyticsService, NotificationService)
├── assets/
│   ├── css/            # Custom CSS
│   └── js/             # Custom JavaScript
├── auth/               # Authentication pages
├── activities/         # Activity CRUD pages
├── weather/            # Weather pages
├── admin/              # Admin panel
├── api/                # API endpoints
├── config/             # Configuration files
├── includes/           # Header & Footer
├── public/             # Public assets
│   └── cache/          # API cache directory
├── database.sql        # Database schema & seed data
├── composer.json       # Composer dependencies
├── .env.example        # Environment variables example
└── README.md           # This file
```

## Endpoint / Routing

### Public
- `GET /` - Redirect ke login/dashboard
- `GET /auth/login.php` - Halaman login
- `POST /auth/login.php` - Proses login
- `GET /auth/register.php` - Halaman registrasi
- `POST /auth/register.php` - Proses registrasi
- `GET /auth/logout.php` - Logout
- `GET /auth/google-login.php` - Google OAuth login
- `GET /auth/google-callback.php` - Google OAuth callback

### Protected (Require Login)
- `GET /dashboard.php` - Dashboard utama
- `GET /activities/index.php` - Daftar aktivitas
- `GET /activities/create.php` - Form tambah aktivitas
- `POST /activities/create.php` - Proses tambah aktivitas
- `GET /activities/edit.php?id={id}` - Form edit aktivitas
- `POST /activities/edit.php` - Proses update aktivitas
- `GET /activities/delete.php?id={id}` - Hapus aktivitas
- `GET /weather/index.php` - Halaman cuaca
- `GET /analytics.php` - Halaman analitik
- `GET /analytics.php?export=csv` - Export CSV

### Admin Only
- `GET /admin/index.php` - Admin dashboard
- `GET /admin/users.php` - Manajemen users
- `GET /admin/activities.php` - Manajemen activities
- `GET /admin/notifications.php` - Manajemen notifications

### API
- `POST /api/push-subscribe.php` - Subscribe untuk Web Push

## Database Schema

### Tabel: users
- id, name, email, password, role, google_id, avatar, created_at, updated_at

### Tabel: activities
- id, user_id, title, description, category, activity_date, start_time, end_time, location, created_at, updated_at

### Tabel: weather_data
- id, location, latitude, longitude, temperature, feels_like, humidity, pressure, wind_speed, wind_direction, condition, description, icon, uv_index, visibility, recorded_at, created_at

### Tabel: notifications
- id, user_id, title, message, type, status, sent_at, created_at

### Tabel: user_locations
- id, user_id, location_name, latitude, longitude, is_default, created_at

### Tabel: push_subscriptions
- id, user_id, endpoint, p256dh, auth, created_at

## Fitur Tema (Siang/Malam)

Aplikasi secara otomatis mengubah tema berdasarkan waktu:
- **Siang (06:00 - 18:00)**: Tema biru (`#3b82f6`)
- **Malam (18:00 - 06:00)**: Tema hitam (`#1a1a2e`)

## API Keys

### OpenWeatherMap
1. Daftar di [OpenWeatherMap](https://openweathermap.org/api)
2. Dapatkan API key gratis
3. Masukkan ke file `.env` sebagai `OWM_API_KEY`

### Google OAuth (Optional)
1. Buat project di [Google Cloud Console](https://console.cloud.google.com/)
2. Enable Google+ API
3. Buat OAuth 2.0 credentials
4. Masukkan Client ID dan Client Secret ke `.env`

## Troubleshooting

### Error: Database connection failed
- Pastikan MySQL/MariaDB berjalan
- Periksa konfigurasi di `.env`
- Pastikan database `cuaca_app` sudah dibuat

### Error: OpenWeatherMap API
- Pastikan API key sudah diisi di `.env`
- Periksa quota API key Anda
- Pastikan koneksi internet tersedia

### Web Push tidak bekerja
- Pastikan VAPID keys sudah di-generate dan diisi di `.env`
- Pastikan menggunakan HTTPS (atau localhost untuk development)
- Periksa browser console untuk error

## Lisensi

Project ini dibuat untuk keperluan akademik.

## Kontributor

- Kelompok 4

