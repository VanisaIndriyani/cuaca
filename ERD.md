# Entity Relationship Diagram (ERD)

## Database Schema: cuaca_app

### Tabel: users
```
┌─────────────────────────────────────┐
│            users                    │
├─────────────────────────────────────┤
│ id (PK, INT, AUTO_INCREMENT)        │
│ name (VARCHAR(255), NOT NULL)       │
│ email (VARCHAR(255), UNIQUE, NOT NULL)│
│ password (VARCHAR(255), NULL)       │
│ role (ENUM: 'admin', 'user')        │
│ google_id (VARCHAR(255), UNIQUE)    │
│ avatar (VARCHAR(500))               │
│ created_at (TIMESTAMP)              │
│ updated_at (TIMESTAMP)               │
└─────────────────────────────────────┘
```

### Tabel: activities
```
┌─────────────────────────────────────┐
│          activities                 │
├─────────────────────────────────────┤
│ id (PK, INT, AUTO_INCREMENT)        │
│ user_id (FK → users.id, NOT NULL)   │
│ title (VARCHAR(255), NOT NULL)       │
│ description (TEXT)                   │
│ category (VARCHAR(100), NOT NULL)    │
│ activity_date (DATE, NOT NULL)       │
│ start_time (TIME)                    │
│ end_time (TIME)                      │
│ location (VARCHAR(255))              │
│ created_at (TIMESTAMP)               │
│ updated_at (TIMESTAMP)               │
└─────────────────────────────────────┘
```

### Tabel: weather_data
```
┌─────────────────────────────────────┐
│        weather_data                 │
├─────────────────────────────────────┤
│ id (PK, INT, AUTO_INCREMENT)        │
│ location (VARCHAR(255), NOT NULL)    │
│ latitude (DECIMAL(10,8))             │
│ longitude (DECIMAL(11,8))            │
│ temperature (DECIMAL(5,2), NOT NULL) │
│ feels_like (DECIMAL(5,2))            │
│ humidity (INT)                       │
│ pressure (INT)                       │
│ wind_speed (DECIMAL(5,2))            │
│ wind_direction (INT)                 │
│ condition (VARCHAR(100))             │
│ description (VARCHAR(255))           │
│ icon (VARCHAR(50))                   │
│ uv_index (DECIMAL(3,1))              │
│ visibility (INT)                     │
│ recorded_at (DATETIME, NOT NULL)     │
│ created_at (TIMESTAMP)               │
└─────────────────────────────────────┘
```

### Tabel: notifications
```
┌─────────────────────────────────────┐
│        notifications                │
├─────────────────────────────────────┤
│ id (PK, INT, AUTO_INCREMENT)        │
│ user_id (FK → users.id, NOT NULL)   │
│ title (VARCHAR(255), NOT NULL)      │
│ message (TEXT, NOT NULL)            │
│ type (VARCHAR(50), DEFAULT 'info')   │
│ status (ENUM: 'pending', 'sent', 'failed')│
│ sent_at (TIMESTAMP)                 │
│ created_at (TIMESTAMP)              │
└─────────────────────────────────────┘
```

### Tabel: user_locations
```
┌─────────────────────────────────────┐
│        user_locations               │
├─────────────────────────────────────┤
│ id (PK, INT, AUTO_INCREMENT)        │
│ user_id (FK → users.id, NOT NULL)   │
│ location_name (VARCHAR(255), NOT NULL)│
│ latitude (DECIMAL(10,8))             │
│ longitude (DECIMAL(11,8))            │
│ is_default (BOOLEAN, DEFAULT FALSE)  │
│ created_at (TIMESTAMP)              │
└─────────────────────────────────────┘
```

### Tabel: push_subscriptions
```
┌─────────────────────────────────────┐
│      push_subscriptions             │
├─────────────────────────────────────┤
│ id (PK, INT, AUTO_INCREMENT)        │
│ user_id (FK → users.id, NOT NULL)   │
│ endpoint (TEXT, NOT NULL)            │
│ p256dh (VARCHAR(255), NOT NULL)     │
│ auth (VARCHAR(255), NOT NULL)        │
│ created_at (TIMESTAMP)              │
└─────────────────────────────────────┘
```

## Relationship Diagram

```
┌─────────────┐
│    users    │
│  (PK: id)   │
└──────┬──────┘
       │
       │ 1:N
       │
       ├─────────────────┐
       │                 │
       ▼                 ▼
┌─────────────┐   ┌──────────────┐
│ activities  │   │ notifications│
│(FK: user_id)│   │(FK: user_id) │
└─────────────┘   └──────────────┘
       │
       │
       ▼
┌─────────────┐
│user_locations│
│(FK: user_id)│
└─────────────┘

┌─────────────┐
│ push_subscriptions│
│(FK: user_id)│
└─────────────┘

┌─────────────┐
│weather_data │
│(No FK)      │
└─────────────┘
```

## Cardinality

- **users** → **activities**: One-to-Many (1 user dapat memiliki banyak activities)
- **users** → **notifications**: One-to-Many (1 user dapat memiliki banyak notifications)
- **users** → **user_locations**: One-to-Many (1 user dapat memiliki banyak lokasi favorit)
- **users** → **push_subscriptions**: One-to-Many (1 user dapat memiliki banyak push subscriptions)
- **weather_data**: Standalone (tidak ada foreign key, data dari API)

## Indexes

### users
- PRIMARY KEY: `id`
- UNIQUE: `email`, `google_id`
- INDEX: `email`, `google_id`

### activities
- PRIMARY KEY: `id`
- FOREIGN KEY: `user_id` → `users.id`
- INDEX: `user_id`, `activity_date`, `category`

### notifications
- PRIMARY KEY: `id`
- FOREIGN KEY: `user_id` → `users.id`
- INDEX: `user_id`, `status`, `created_at`

### user_locations
- PRIMARY KEY: `id`
- FOREIGN KEY: `user_id` → `users.id`
- INDEX: `user_id`

### push_subscriptions
- PRIMARY KEY: `id`
- FOREIGN KEY: `user_id` → `users.id`
- INDEX: `user_id`

### weather_data
- PRIMARY KEY: `id`
- INDEX: `location`, `recorded_at`

## Constraints

- **CASCADE DELETE**: 
  - Jika user dihapus, semua activities, notifications, user_locations, dan push_subscriptions terkait juga dihapus
- **UNIQUE Constraints**:
  - `users.email` harus unique
  - `users.google_id` harus unique (jika ada)
- **NOT NULL Constraints**:
  - Semua primary keys
  - Foreign keys
  - Field yang required untuk operasi aplikasi

