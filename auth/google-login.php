<?php
require_once __DIR__ . '/../config/config.php';

// Check if vendor autoload exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Please run: composer install');
}

require_once __DIR__ . '/../vendor/autoload.php';

// Load Google OAuth config
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

$client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$client_secret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
$redirect_uri = $_ENV['GOOGLE_REDIRECT_URI'] ?? base_url('auth/google-callback.php');

if (empty($client_id) || empty($client_secret)) {
    $_SESSION['error'] = 'Google OAuth tidak dikonfigurasi. Silakan set GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di .env';
    redirect('auth/login.php');
}

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope('email');
$client->addScope('profile');

// Configure HTTP client to handle SSL properly
// For production: use SSL verification (secure)
// For development: disable SSL verification (only for localhost)
$is_production = !empty($_ENV['APP_URL']) && strpos($_ENV['APP_URL'], 'localhost') === false && strpos($_ENV['APP_URL'], '127.0.0.1') === false;
$httpClient = new \GuzzleHttp\Client([
    'verify' => $is_production, // Enable SSL verification for production, disable for local development
]);
$client->setHttpClient($httpClient);

$auth_url = $client->createAuthUrl();
header('Location: ' . $auth_url);
exit;

