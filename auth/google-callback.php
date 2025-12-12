<?php
require_once __DIR__ . '/../config/config.php';

// Check if vendor autoload exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    $_SESSION['error'] = 'Vendor autoload tidak ditemukan. Silakan jalankan: composer install';
    redirect('auth/login.php');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Models/User.php';

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

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (!isset($token['error'])) {
            $client->setAccessToken($token);
            $oauth = new Google_Service_Oauth2($client);
            $user_info = $oauth->userinfo->get();
            
            $user = new User($db);
            
            // Check if user exists by Google ID
            $existing_user = $user->findByGoogleId($user_info->id);
            
            if ($existing_user) {
                // Check if user is deactivated (with auto-reactivate if period has passed)
                if ($user->isDeactivated($existing_user, true)) {
                    $_SESSION['error'] = $user->getDeactivationMessage($existing_user);
                    redirect('auth/login.php');
                }
                
                // Login existing user
                $_SESSION['user_id'] = $existing_user['id'];
                $_SESSION['user_name'] = $existing_user['name'];
                $_SESSION['user_email'] = $existing_user['email'];
                $_SESSION['user_role'] = $existing_user['role'];
                if (!empty($existing_user['avatar'])) {
                    if (strpos($existing_user['avatar'], 'http') === 0) {
                        $_SESSION['user_avatar'] = $existing_user['avatar'];
                    } else {
                        $_SESSION['user_avatar'] = base_url('public' . $existing_user['avatar']);
                    }
                } else {
                    $_SESSION['user_avatar'] = null;
                }
            } else {
                // Check if user exists by email (to link Google account)
                $existing_by_email = $user->findByEmail($user_info->email);
                
                if ($existing_by_email) {
                    // Link Google account to existing user
                    $google_data = [
                        'id' => $user_info->id,
                        'name' => $user_info->name,
                        'email' => $user_info->email,
                        'picture' => $user_info->picture
                    ];
                    
                    if ($user->createFromGoogle($google_data)) {
                        $updated_user = $user->findByGoogleId($user_info->id);
                        
                        // Check if user is deactivated (with auto-reactivate if period has passed)
                        if ($user->isDeactivated($updated_user, true)) {
                            $_SESSION['error'] = $user->getDeactivationMessage($updated_user);
                            redirect('auth/login.php');
                        }
                        
                        $_SESSION['user_id'] = $updated_user['id'];
                        $_SESSION['user_name'] = $updated_user['name'];
                        $_SESSION['user_email'] = $updated_user['email'];
                        $_SESSION['user_role'] = $updated_user['role'];
                        if (!empty($updated_user['avatar'])) {
                            if (strpos($updated_user['avatar'], 'http') === 0) {
                                $_SESSION['user_avatar'] = $updated_user['avatar'];
                            } else {
                                $_SESSION['user_avatar'] = base_url('public' . $updated_user['avatar']);
                            }
                        } else {
                            $_SESSION['user_avatar'] = null;
                        }
                    }
                } else {
                    // Create new user from Google
                    $google_data = [
                        'id' => $user_info->id,
                        'name' => $user_info->name,
                        'email' => $user_info->email,
                        'picture' => $user_info->picture
                    ];
                    
                    if ($user->createFromGoogle($google_data)) {
                        $new_user = $user->findByGoogleId($user_info->id);
                        if ($new_user) {
                            // Check if user is deactivated (shouldn't happen for new users, but check anyway)
                            if ($user->isDeactivated($new_user, true)) {
                                $_SESSION['error'] = $user->getDeactivationMessage($new_user);
                                redirect('auth/login.php');
                            }
                            
                            $_SESSION['user_id'] = $new_user['id'];
                            $_SESSION['user_name'] = $new_user['name'];
                            $_SESSION['user_email'] = $new_user['email'];
                            $_SESSION['user_role'] = $new_user['role'];
                            if (!empty($new_user['avatar'])) {
                                if (strpos($new_user['avatar'], 'http') === 0) {
                                    $_SESSION['user_avatar'] = $new_user['avatar'];
                                } else {
                                    $_SESSION['user_avatar'] = base_url('public' . $new_user['avatar']);
                                }
                            } else {
                                $_SESSION['user_avatar'] = null;
                            }
                        } else {
                            $_SESSION['error'] = 'Gagal membuat akun dari Google. Silakan coba lagi.';
                            redirect('auth/login.php');
                        }
                    } else {
                        $_SESSION['error'] = 'Gagal membuat akun dari Google. Silakan coba lagi.';
                        redirect('auth/login.php');
                    }
                }
            }
            
            redirectAfterLogin();
        } else {
            $_SESSION['error'] = 'Autentikasi Google gagal: ' . ($token['error'] ?? 'Unknown error');
            redirect('auth/login.php');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        redirect('auth/login.php');
    }
} else if (isset($_GET['error'])) {
    $_SESSION['error'] = 'Autentikasi Google dibatalkan atau gagal.';
    redirect('auth/login.php');
} else {
    redirect('auth/login.php');
}

