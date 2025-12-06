<?php
/**
 * Test SMTP Configuration untuk Hosting
 * File ini khusus untuk test di hosting (production)
 * Akses: https://bitubi.my.id/cuaca/test-smtp-hosting.php
 */

// Enable error reporting untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Services/EmailService.php';

// Load .env
$envFile = __DIR__ . '/.env';
$env_exists = file_exists($envFile);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test SMTP Configuration (Hosting)</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; border-left: 4px solid #3b82f6; }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; background: #dbeafe; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f59e0b; background: #fef3c7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .test-form { margin-top: 20px; }
        input[type="email"] { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2563eb; }
        .log-section { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test SMTP Configuration (Hosting/Production)</h1>
        
        <div class="section">
            <h2>1. Check Environment</h2>
            <?php
            echo '<div class="' . ($env_exists ? 'success' : 'error') . '">';
            echo $env_exists ? '✅ File .env ditemukan' : '❌ File .env TIDAK ditemukan di: ' . htmlspecialchars($envFile);
            echo '</div>';
            
            echo '<pre>';
            echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'Not defined') . "\n";
            echo "PHP Version: " . phpversion() . "\n";
            echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
            echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
            echo "Script Path: " . __DIR__ . "\n";
            echo '</pre>';
            ?>
        </div>
        
        <div class="section">
            <h2>2. Check PHPMailer</h2>
            <?php
            $vendorAutoload = __DIR__ . '/vendor/autoload.php';
            if (file_exists($vendorAutoload)) {
                require_once $vendorAutoload;
                echo '<div class="success">✅ PHPMailer autoload ditemukan</div>';
                
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    echo '<div class="success">✅ PHPMailer class tersedia</div>';
                } else {
                    echo '<div class="error">❌ PHPMailer class tidak ditemukan</div>';
                }
            } else {
                echo '<div class="error">❌ Vendor autoload tidak ditemukan. Jalankan: composer install</div>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>3. Check .env Configuration</h2>
            <?php
            if ($env_exists) {
                $smtp_host = $_ENV['SMTP_HOST'] ?? 'Not set';
                $smtp_port = $_ENV['SMTP_PORT'] ?? 'Not set';
                $smtp_user = $_ENV['SMTP_USER'] ?? 'Not set';
                $smtp_pass = $_ENV['SMTP_PASS'] ?? 'Not set';
                $smtp_from = $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USER'] ?? 'Not set';
                $smtp_from_name = $_ENV['SMTP_FROM_NAME'] ?? 'Not set';
                
                echo '<pre>';
                echo "SMTP_HOST: " . htmlspecialchars($smtp_host) . "\n";
                echo "SMTP_PORT: " . htmlspecialchars($smtp_port) . "\n";
                echo "SMTP_USER: " . htmlspecialchars($smtp_user) . "\n";
                echo "SMTP_PASS: " . (!empty($smtp_pass) ? '***' . substr($smtp_pass, -4) . ' (' . strlen($smtp_pass) . ' chars)' : 'Not set') . "\n";
                echo "SMTP_FROM: " . htmlspecialchars($smtp_from) . "\n";
                echo "SMTP_FROM_NAME: " . htmlspecialchars($smtp_from_name) . "\n";
                echo '</pre>';
                
                if (empty($smtp_user) || empty($smtp_pass)) {
                    echo '<div class="error">❌ SMTP_USER atau SMTP_PASS belum diisi di .env</div>';
                } else {
                    echo '<div class="success">✅ Konfigurasi SMTP lengkap</div>';
                }
            } else {
                echo '<div class="error">❌ File .env tidak ditemukan. Pastikan file .env sudah di-upload ke hosting.</div>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>4. Test Send Email</h2>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
                $test_email = $_POST['test_email'];
                echo '<div class="info">🔄 Mengirim test email ke: ' . htmlspecialchars($test_email) . '</div>';
                
                try {
                    $emailService = new EmailService();
                    $result = $emailService->sendEmail(
                        $test_email,
                        'Test Email dari ' . APP_NAME . ' (Hosting)',
                        '<h1>Test Email dari Hosting</h1><p>Jika Anda menerima email ini, berarti konfigurasi SMTP di hosting sudah benar!</p><p>Waktu: ' . date('Y-m-d H:i:s') . '</p>',
                        true
                    );
                    
                    if ($result) {
                        echo '<div class="success">✅ Email berhasil dikirim! Cek inbox email Anda.</div>';
                    } else {
                        echo '<div class="error">❌ Gagal mengirim email.</div>';
                        
                        // Check email error log
                        $email_log_file = __DIR__ . '/logs/email_errors.log';
                        if (file_exists($email_log_file)) {
                            echo '<div class="warning">📋 Error Log:</div>';
                            echo '<div class="log-section"><pre>' . htmlspecialchars(file_get_contents($email_log_file)) . '</pre></div>';
                        }
                        
                        // Check PHP error log
                        $php_error_log = ini_get('error_log');
                        if ($php_error_log && file_exists($php_error_log)) {
                            $file_size = filesize($php_error_log);
                            $read_size = min(5000, $file_size);
                            $recent_errors = $read_size > 0 ? file_get_contents($php_error_log, false, null, max(0, $file_size - $read_size)) : '';
                            if (!empty($recent_errors)) {
                                echo '<div class="warning">📋 PHP Error Log (last 5000 chars):</div>';
                                echo '<div class="log-section"><pre>' . htmlspecialchars($recent_errors) . '</pre></div>';
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="error">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    echo '<div class="warning">Stack Trace:</div>';
                    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                }
            }
            ?>
            
            <form method="POST" class="test-form">
                <label>Email untuk test:</label><br>
                <input type="email" name="test_email" placeholder="your-email@gmail.com" required>
                <button type="submit">Kirim Test Email</button>
            </form>
        </div>
        
        <div class="section">
            <h2>5. Check Error Logs</h2>
            <?php
            $email_log_file = __DIR__ . '/logs/email_errors.log';
            if (file_exists($email_log_file)) {
                echo '<div class="info">📋 Email Error Log ditemukan:</div>';
                echo '<div class="log-section"><pre>' . htmlspecialchars(file_get_contents($email_log_file)) . '</pre></div>';
            } else {
                echo '<div class="warning">⚠️ Email error log belum ada (belum ada error atau log belum dibuat)</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>

