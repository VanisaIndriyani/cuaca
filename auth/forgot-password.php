<?php
// Start output buffering to prevent "headers already sent" warning
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Services/EmailService.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Email harus diisi';
    } else {
        $user = new User($db);
        $userData = $user->findByEmail($email);
        
        if (!$userData) {
            // Email tidak terdaftar
            $error = 'Email tidak terdaftar. Silakan cek kembali email Anda atau daftar terlebih dahulu.';
        } else {
            // Generate 6-digit code
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Save code to database
            if ($user->saveResetCode($email, $code)) {
                // Send email
                $emailService = new EmailService();
                if ($emailService->sendPasswordResetCode($email, $code, $userData['name'])) {
                    // Clear any output before redirect
                    ob_clean();
                    // Redirect langsung ke halaman verifikasi kode
                    header('Location: ' . base_url('auth/verify-code.php?email=' . urlencode($email)));
                    exit;
                } else {
                    // Log error for debugging
                    error_log("Failed to send reset code email to: $email");
                    
                    // Check error log for authentication issues
                    $error_log_file = ini_get('error_log');
                    $recent_errors = '';
                    if ($error_log_file && file_exists($error_log_file)) {
                        // Get last 2000 characters of error log
                        $file_size = filesize($error_log_file);
                        $read_size = min(2000, $file_size);
                        $recent_errors = $read_size > 0 ? file_get_contents($error_log_file, false, null, max(0, $file_size - $read_size)) : '';
                    }
                    
                    // Also check custom email error log
                    $email_log_file = __DIR__ . '/../logs/email_errors.log';
                    if (file_exists($email_log_file)) {
                        $email_errors = file_get_contents($email_log_file);
                        $recent_errors .= $email_errors;
                    }
                    
                    $error_msg = 'Gagal mengirim email. ';
                    
                    // Check if SMTP is configured
                    if (empty($_ENV['SMTP_USER']) || empty($_ENV['SMTP_PASS'])) {
                        $error_msg .= 'Konfigurasi SMTP belum lengkap. Pastikan SMTP_USER dan SMTP_PASS sudah diisi di .env';
                        error_log("SMTP Configuration Missing - SMTP_USER: " . (!empty($_ENV['SMTP_USER']) ? 'Set' : 'Empty') . ", SMTP_PASS: " . (!empty($_ENV['SMTP_PASS']) ? 'Set' : 'Empty'));
                    } elseif (strpos($recent_errors, '535') !== false || strpos($recent_errors, 'BadCredentials') !== false || strpos($recent_errors, 'Could not authenticate') !== false) {
                        $error_msg .= '<strong>Error Autentikasi Gmail:</strong><br>';
                        $error_msg .= '• Pastikan menggunakan <strong>App Password</strong> (bukan password Gmail biasa)<br>';
                        $error_msg .= '• Pastikan 2-Step Verification sudah aktif di Gmail<br>';
                        $error_msg .= '• Cara membuat App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: #3b82f6;">Klik di sini</a>';
                    } else {
                        $error_msg .= 'Pastikan konfigurasi SMTP di .env sudah benar. ';
                        $is_production = !empty($_ENV['APP_URL']) && strpos($_ENV['APP_URL'], 'localhost') === false && strpos($_ENV['APP_URL'], '127.0.0.1') === false;
                        if ($is_production) {
                            $error_msg .= 'Cek file <code>logs/email_errors.log</code> untuk detail error.';
                        } else {
                            $error_msg .= 'Cek error log untuk detail lebih lanjut.';
                        }
                    }
                    
                    $error = $error_msg;
                }
            } else {
                $error = 'Gagal menyimpan kode reset. Silakan coba lagi.';
            }
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirectAfterLogin();
}

$page_title = 'Lupa Password';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <style>
        /* ===== FORGOT PASSWORD PAGE - BLUE YELLOW WHITE THEME ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
            transition: background 0.5s ease;
        }

        /* Tema Malam (Hitam) */
        body.night {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            background-size: 400% 400%;
        }

        body.night::before {
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
        }

        body.night::after {
            background: radial-gradient(circle, rgba(255, 255, 255, 0.03) 0%, transparent 70%);
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Decorative circles */
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.15) 0%, transparent 70%);
            top: -200px;
            right: -200px;
            border-radius: 50%;
            animation: float 20s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            bottom: -150px;
            left: -150px;
            border-radius: 50%;
            animation: float 25s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, 30px) scale(1.1); }
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
        }
        
        .forgot-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5);
            padding: 2.5rem;
            width: 100%;
            animation: fadeUp 0.6s ease;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .forgot-header i {
            font-size: 4rem;
            color: #fbbf24;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(251, 191, 36, 0.3));
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .forgot-header h2 {
            color: #1e40af;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
        }
        
        .forgot-header p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #3b82f6;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        }

        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-to-login a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .back-to-login a:hover {
            color: #1e40af;
            text-decoration: underline;
            transform: translateX(-3px);
        }

        /* Responsive */
        @media (max-width: 576px) {
            .forgot-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }

            .forgot-header i {
                font-size: 3rem;
            }

            .forgot-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-card">
        <div class="forgot-header">
            <i class="bi bi-key"></i>
            <h2>Lupa Password?</h2>
            <p>Masukkan email Anda untuk mendapatkan kode reset password</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="line-height: 1.6;">
                <i class="bi bi-exclamation-circle"></i> 
                <div style="display: inline-block; margin-left: 0.5rem;">
                    <?php 
                    // Check if error contains HTML (authentication error)
                    if (strpos($error, '<strong>') !== false || strpos($error, '<br>') !== false) {
                        echo $error; // Don't escape HTML for detailed error messages
                    } else {
                        echo htmlspecialchars($error); // Escape HTML for simple error messages
                    }
                    ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope"></i> Email
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="nama@email.com" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-send"></i> Kirim Kode Reset
            </button>
        </form>
        
        <div class="back-to-login">
            <a href="<?php echo base_url('auth/login.php'); ?>">
                <i class="bi bi-arrow-left"></i> Kembali ke Login
            </a>
        </div>
    </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Deteksi waktu dan set tema
        function setTheme() {
            const currentHour = new Date().getHours();
            const body = document.body;
            
            // Siang: 6:00 - 17:59 (biru)
            // Malam: 18:00 - 5:59 (hitam)
            if (currentHour >= 6 && currentHour < 18) {
                body.classList.remove('night');
            } else {
                body.classList.add('night');
            }
        }

        // Set tema saat halaman dimuat
        setTheme();
    </script>
</body>
</html>

