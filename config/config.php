<?php
require_once __DIR__ . '/database.php';

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// App Configuration
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/cuaca');
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Cuaca & Aktivitas Harian');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'Asia/Jakarta');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Database
$database = new Database();
$db = $database->getConnection();

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function untuk base URL
function base_url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

// Helper function untuk redirect
function redirect($path) {
    header('Location: ' . base_url($path));
    exit;
}

// Helper function untuk redirect setelah login (admin ke admin panel, user ke dashboard)
function redirectAfterLogin() {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('dashboard.php');
    }
}

// Helper function untuk check login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function untuk check admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Helper function untuk require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

// Helper function untuk require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('dashboard.php');
    }
}

// Helper function untuk menampilkan role (user -> guest)
function displayRole($role) {
    if ($role === 'user') {
        return 'guest';
    }
    return $role;
}

