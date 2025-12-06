-- Database: cuaca_app
-- Aplikasi Cuaca dan Aktivitas Harian

CREATE DATABASE IF NOT EXISTS cuaca_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cuaca_app;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    google_id VARCHAR(255) NULL UNIQUE,
    avatar VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: activities
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NOT NULL,
    activity_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    location VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_date (activity_date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: weather_data
CREATE TABLE IF NOT EXISTS weather_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    temperature DECIMAL(5, 2) NOT NULL,
    feels_like DECIMAL(5, 2) NULL,
    humidity INT NULL,
    pressure INT NULL,
    wind_speed DECIMAL(5, 2) NULL,
    wind_direction INT NULL,
    `condition` VARCHAR(100) NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(50) NULL,
    uv_index DECIMAL(3, 1) NULL,
    visibility INT NULL,
    recorded_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location (location),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_read_at (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_locations (untuk menyimpan lokasi favorit user)
CREATE TABLE IF NOT EXISTS user_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    location_name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: push_subscriptions (untuk Web Push)
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data: Admin User
-- Password: admin123 (hashed)
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@cuaca.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('User Demo', 'user@cuaca.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Seed Data: Sample Activities
INSERT INTO activities (user_id, title, description, category, activity_date, start_time, end_time, location) VALUES
(2, 'Jogging Pagi', 'Jogging di taman kota', 'olahraga', CURDATE(), '06:00:00', '07:00:00', 'Taman Kota'),
(2, 'Kuliah Pemrograman Web', 'Mata kuliah pemrograman web', 'pendidikan', CURDATE(), '08:00:00', '10:00:00', 'Kampus'),
(2, 'Futsal', 'Futsal dengan teman', 'olahraga', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00', '18:00:00', 'Lapangan Futsal'),
(2, 'Belajar PHP', 'Belajar PHP native', 'pendidikan', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00', '21:00:00', 'Rumah'),
(2, 'Meeting Tim', 'Meeting dengan tim project', 'kerja', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '12:00:00', 'Kantor');

-- Seed Data: Sample Weather Data (untuk testing)
INSERT INTO weather_data (location, latitude, longitude, temperature, feels_like, humidity, pressure, wind_speed, `condition`, description, icon, recorded_at) VALUES
('Jakarta', -6.2088, 106.8456, 28.5, 30.2, 75, 1013, 15, 'Clouds', 'Berawan', '04d', NOW()),
('Bandung', -6.9175, 107.6191, 24.3, 25.1, 80, 1015, 12, 'Rain', 'Hujan ringan', '10d', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Surabaya', -7.2575, 112.7521, 32.1, 34.5, 65, 1010, 18, 'Clear', 'Cerah', '01d', DATE_SUB(NOW(), INTERVAL 2 DAY));

