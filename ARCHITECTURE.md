# Diagram Arsitektur Sistem

## Arsitektur Umum

```
┌─────────────────────────────────────────────────────────────┐
│                         CLIENT (Browser)                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐        │
│  │   HTML/CSS   │  │  JavaScript  │  │ Service Worker│       │
│  │   Bootstrap  │  │   Chart.js   │  │  (Web Push)  │       │
│  └──────────────┘  └──────────────┘  └──────────────┘        │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ HTTP/HTTPS
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      WEB SERVER (Apache/Nginx)                │
│                      PHP Native (No Framework)                │
└─────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│   Models     │   │   Services   │   │   Config     │
│              │   │              │   │              │
│ - User       │   │ - ApiClient  │   │ - Database   │
│ - Activity   │   │ - Analytics  │   │ - Config     │
│ - WeatherData│   │ - Notification│   │              │
│ - Notification│   │              │   │              │
└──────────────┘   └──────────────┘   └──────────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE (MySQL/MariaDB)                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │  users   │  │activities│  │weather_  │  │notifications│ │
│  │          │  │          │  │  data    │  │            │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            │
        ┌───────────────────┴───────────────────┐
        │                                       │
        ▼                                       ▼
┌──────────────┐                       ┌──────────────┐
│ OpenWeatherMap│                       │ Google OAuth │
│     API      │                       │     API      │
└──────────────┘                       └──────────────┘
```

## Flow Autentikasi

```
User
 │
 │ 1. Request Login
 ▼
┌─────────────┐
│ login.php   │
└─────────────┘
 │
 │ 2. POST credentials
 ▼
┌─────────────┐
│ User Model  │───► Verify password_hash()
└─────────────┘
 │
 │ 3. Success
 ▼
┌─────────────┐
│ Set Session │
└─────────────┘
 │
 │ 4. Redirect
 ▼
┌─────────────┐
│ dashboard.php│
└─────────────┘
```

## Flow Cuaca

```
User Request Weather
 │
 │ 1. Input location
 ▼
┌─────────────────┐
│ weather/index.php│
└─────────────────┘
 │
 │ 2. Call ApiClientWeather
 ▼
┌─────────────────┐
│ ApiClientWeather│
└─────────────────┘
 │
 │ 3. Check Cache
 │    ├─► Cache Hit → Return cached data
 │    └─► Cache Miss → Continue
 │
 │ 4. Call OpenWeatherMap API
 ▼
┌─────────────────┐
│ OpenWeatherMap   │
└─────────────────┘
 │
 │ 5. Save to Cache & DB
 ▼
┌─────────────────┐
│ WeatherData Model│
└─────────────────┘
 │
 │ 6. Return to View
 ▼
┌─────────────────┐
│ Display Weather │
└─────────────────┘
```

## Flow CRUD Activities

```
User
 │
 │ 1. Create/Edit/Delete
 ▼
┌─────────────────┐
│ activities/*.php│
└─────────────────┘
 │
 │ 2. Validate & Process
 ▼
┌─────────────────┐
│ Activity Model  │
└─────────────────┘
 │
 │ 3. Database Operation
 ▼
┌─────────────────┐
│ MySQL Database   │
└─────────────────┘
 │
 │ 4. Success Response
 ▼
┌─────────────────┐
│ Redirect/Display │
└─────────────────┘
```

## Class Diagram (UML)

```
┌─────────────────────┐
│      Database       │
└─────────────────────┘
         ▲
         │
         │ uses
         │
┌─────────────────────┐
│      Models         │
├─────────────────────┤
│ + User              │
│ + Activity          │
│ + WeatherData       │
│ + Notification      │
└─────────────────────┘
         ▲
         │
         │ uses
         │
┌─────────────────────┐
│     Services        │
├─────────────────────┤
│ + ApiClientWeather  │
│ + AnalyticsService│
│ + NotificationService│
└─────────────────────┘
         ▲
         │
         │ uses
         │
┌─────────────────────┐
│   Controllers/Pages │
├─────────────────────┤
│ + dashboard.php     │
│ + activities/*.php  │
│ + weather/*.php     │
│ + analytics.php     │
└─────────────────────┘
```

## Database ERD

```
┌─────────────┐         ┌──────────────┐
│    users    │         │  activities  │
├─────────────┤         ├──────────────┤
│ id (PK)     │◄──┐     │ id (PK)      │
│ name        │   │     │ user_id (FK) │
│ email       │   │     │ title        │
│ password    │   │     │ category     │
│ role        │   │     │ activity_date│
│ google_id   │   │     │ start_time   │
│ avatar      │   │     │ end_time     │
└─────────────┘   │     │ location     │
                  │     └──────────────┘
                  │
                  │     ┌──────────────┐
                  │     │ notifications│
                  └─────┤ id (PK)      │
                        │ user_id (FK) │
                        │ title        │
                        │ message      │
                        │ status       │
                        └──────────────┘

┌─────────────┐
│weather_data │
├─────────────┤
│ id (PK)     │
│ location    │
│ temperature │
│ humidity    │
│ recorded_at │
└─────────────┘
```

## Teknologi Stack

### Backend
- PHP 7.4+ (Native, tanpa framework)
- PDO untuk database access
- Session-based authentication

### Frontend
- HTML5
- CSS3 (Bootstrap 5)
- JavaScript (Vanilla + Chart.js)
- Service Worker (Web Push)

### Database
- MySQL/MariaDB
- PDO dengan prepared statements

### External APIs
- OpenWeatherMap API
- Google OAuth 2.0 API

### Libraries (via Composer)
- google/apiclient (Google OAuth)
- phpmailer/phpmailer (Email, optional)
- firebase/php-jwt (JWT, optional)

