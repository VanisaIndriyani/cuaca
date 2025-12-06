# Daftar Endpoint / Routing

## Public Endpoints

### Authentication
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/` | Redirect ke login atau dashboard |
| GET | `/auth/login.php` | Halaman login |
| POST | `/auth/login.php` | Proses login |
| GET | `/auth/register.php` | Halaman registrasi |
| POST | `/auth/register.php` | Proses registrasi |
| GET | `/auth/logout.php` | Logout user |
| GET | `/auth/google-login.php` | Redirect ke Google OAuth |
| GET | `/auth/google-callback.php` | Callback dari Google OAuth |

## Protected Endpoints (Require Login)

### Dashboard
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/dashboard.php` | Dashboard utama dengan cuaca dan aktivitas hari ini |

### Activities (CRUD)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/activities/index.php` | Daftar semua aktivitas user |
| GET | `/activities/create.php` | Form tambah aktivitas baru |
| POST | `/activities/create.php` | Proses simpan aktivitas baru |
| GET | `/activities/edit.php?id={id}` | Form edit aktivitas |
| POST | `/activities/edit.php` | Proses update aktivitas |
| GET | `/activities/delete.php?id={id}` | Hapus aktivitas |

**Query Parameters untuk `/activities/index.php`:**
- `date` - Filter berdasarkan tanggal (format: Y-m-d)
- `category` - Filter berdasarkan kategori (olahraga, pendidikan, kerja, istirahat, lainnya)

### Weather
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/weather/index.php` | Halaman informasi cuaca |
| GET | `/weather/index.php?location={city}` | Cuaca untuk kota tertentu |
| GET | `/weather/index.php?lat={lat}&lon={lon}` | Cuaca berdasarkan koordinat |

### Analytics
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/analytics.php` | Halaman analitik dan grafik |
| GET | `/analytics.php?export=csv&start_date={date}&end_date={date}` | Export laporan CSV |

**Query Parameters:**
- `start_date` - Tanggal mulai (format: Y-m-d)
- `end_date` - Tanggal akhir (format: Y-m-d)

## Admin Endpoints (Require Admin Role)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/index.php` | Dashboard admin |
| GET | `/admin/users.php` | Manajemen users |
| GET | `/admin/activities.php` | Manajemen semua activities |
| GET | `/admin/notifications.php` | Manajemen notifications |

## API Endpoints

### Web Push
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/push-subscribe.php` | Subscribe untuk Web Push notifications |

**Request Body:**
```json
{
  "subscription": {
    "endpoint": "https://...",
    "keys": {
      "p256dh": "...",
      "auth": "..."
    }
  }
}
```

## Response Format

### Success
```json
{
  "success": true,
  "data": {...}
}
```

### Error
```json
{
  "error": "Error message"
}
```

## Authentication

### Session-based
- Setelah login, session disimpan dengan key:
  - `user_id`
  - `user_name`
  - `user_email`
  - `user_role`
  - `user_avatar`

### Google OAuth
- Flow: `/auth/google-login.php` → Google → `/auth/google-callback.php` → Dashboard

## Error Codes

- `401` - Unauthorized (belum login)
- `403` - Forbidden (bukan admin)
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

