<?php
/**
 * Test SMTP Configuration
 * File ini untuk test konfigurasi SMTP di local
 * Akses: http://localhost/cuaca/test-smtp.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Services/EmailService.php';

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test SMTP Configuration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 5px; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 5px; }
        .info { color: #3b82f6; background: #dbeafe; padding: 10px; border-radius: 5px; }
        .warning { color: #f59e0b; background: #fef3c7; padding: 10px; border-radius: 5px; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .test-form { margin-top: 20px; }
        input[type="email"] { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test SMTP Configuration</h1>
        
        <div class="section">
            <h2>1. Check PHPMailer</h2>
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
            <h2>2. Check .env Configuration</h2>
            <?php
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
            echo "SMTP_PASS: " . (!empty($smtp_pass) ? '***' . substr($smtp_pass, -4) : 'Not set') . "\n";
            echo "SMTP_FROM: " . htmlspecialchars($smtp_from) . "\n";
            echo "SMTP_FROM_NAME: " . htmlspecialchars($smtp_from_name) . "\n";
            echo '</pre>';
            
            if (empty($smtp_user) || empty($smtp_pass)) {
                echo '<div class="error">❌ SMTP_USER atau SMTP_PASS belum diisi di .env</div>';
            } else {
                echo '<div class="success">✅ Konfigurasi SMTP lengkap</div>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>3. Test Send Email</h2>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
                $test_email = $_POST['test_email'];
                echo '<div class="info">🔄 Mengirim test email ke: ' . htmlspecialchars($test_email) . '</div>';
                
                try {
                    $emailService = new EmailService();
                    $result = $emailService->sendEmail(
                        $test_email,
                        'Test Email dari ' . APP_NAME,
                        '<h1>Test Email</h1><p>Jika Anda menerima email ini, berarti konfigurasi SMTP sudah benar!</p>',
                        true
                    );
                    
                    if ($result) {
                        echo '<div class="success">✅ Email berhasil dikirim! Cek inbox email Anda.</div>';
                    } else {
                        echo '<div class="error">❌ Gagal mengirim email. Cek error log untuk detail.</div>';
                        
                        // Check for authentication errors
                        $error_log_file = ini_get('error_log');
                        $recent_errors = '';
                        if ($error_log_file && file_exists($error_log_file)) {
                            $recent_errors = file_get_contents($error_log_file);
                        }
                        
                        if (strpos($recent_errors, '535') !== false || strpos($recent_errors, 'BadCredentials') !== false || strpos($recent_errors, 'Could not authenticate') !== false) {
                            echo '<div class="error" style="margin-top: 15px; padding: 15px;">';
                            echo '<strong>🔐 ERROR AUTENTIKASI GMAIL</strong><br><br>';
                            echo 'Gmail menolak kredensial Anda. Ini berarti:<br>';
                            echo '• Anda menggunakan <strong>password Gmail biasa</strong> (tidak bisa digunakan)<br>';
                            echo '• Gmail <strong>WAJIB</strong> menggunakan <strong>App Password</strong><br><br>';
                            echo '<strong>Solusi:</strong><br>';
                            echo '1. Aktifkan <strong>2-Step Verification</strong> di: <a href="https://myaccount.google.com/security" target="_blank" style="color: #3b82f6;">https://myaccount.google.com/security</a><br>';
                            echo '2. Buat <strong>App Password</strong> di: <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: #3b82f6;">https://myaccount.google.com/apppasswords</a><br>';
                            echo '3. Update <code>SMTP_PASS</code> di file <code>.env</code> dengan App Password (16 karakter)<br>';
                            echo '4. Test lagi<br><br>';
                            echo '📖 Lihat panduan lengkap di: <code>GMAIL_APP_PASSWORD_SETUP.md</code>';
                            echo '</div>';
                        } else {
                            echo '<div class="warning">💡 Cek file error log PHP untuk detail error (biasanya di error_log atau cPanel error log)</div>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
            <h2>4. Troubleshooting</h2>
            <div class="warning">
                <strong>Jika email tidak terkirim, cek:</strong>
                <ul>
                    <li>Pastikan SMTP_USER dan SMTP_PASS sudah diisi di .env</li>
                    <li>Untuk Gmail, pastikan menggunakan <strong>App Password</strong> (bukan password biasa)</li>
                    <li>Pastikan 2-Step Verification sudah aktif di Gmail</li>
                    <li>Cek error log PHP untuk detail error</li>
                    <li>Pastikan port 587 tidak diblokir firewall</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>

