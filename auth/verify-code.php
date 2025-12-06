<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

$error = '';
$email = $_GET['email'] ?? '';

if (empty($email)) {
    redirect('auth/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    
    if (empty($code)) {
        $error = 'Kode harus diisi';
    } else {
        $user = new User($db);
        
        // Verify code
        if ($user->verifyResetCode($email, $code)) {
            // Code verified, redirect to reset password
            $_SESSION['reset_email'] = $email;
            redirect('auth/reset-password.php');
        } else {
            $error = 'Kode tidak valid atau sudah kedaluwarsa. Silakan coba lagi.';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirectAfterLogin();
}

$page_title = 'Verifikasi Kode';
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
        /* ===== VERIFY CODE PAGE - BLUE YELLOW WHITE THEME ===== */
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
        
        .verify-card {
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
        
        .verify-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .verify-header i {
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
        
        .verify-header h2 {
            color: #1e40af;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
        }
        
        .verify-header p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            font-family: 'Courier New', monospace;
            background: #ffffff;
            color: #1f2937;
        }

        /* Warna untuk pagi (biru) */
        .form-control.morning {
            background: #dbeafe;
            color: #1e40af;
            border-color: #3b82f6;
        }

        /* Warna untuk malam (item/hitam) */
        .form-control.night {
            background: #1f2937;
            color: #ffffff;
            border-color: #374151;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
        }

        .form-control.morning:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }

        .form-control.night:focus {
            border-color: #4b5563;
            box-shadow: 0 0 0 4px rgba(75, 85, 99, 0.3);
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

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #1e40af;
            text-decoration: underline;
            transform: translateX(-3px);
        }
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-box i {
            color: #3b82f6;
        }

        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            text-align: center;
            display: block;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .verify-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }

            .verify-header i {
                font-size: 3rem;
            }

            .verify-header h2 {
                font-size: 1.5rem;
            }

            .form-control {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verify-card">
        <div class="verify-header">
            <i class="bi bi-shield-check"></i>
            <h2>Verifikasi Kode</h2>
            <p>Masukkan kode yang dikirim ke email Anda</p>
        </div>
        
        <div class="info-box">
            <p><i class="bi bi-info-circle"></i> Kode telah dikirim ke: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="verifyForm">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <div class="mb-3">
                <label for="code" class="form-label text-center d-block">
                    <strong>Kode Verifikasi (6 digit)</strong>
                </label>
                <input type="text" class="form-control" id="code" name="code" 
                       placeholder="000000" maxlength="6" required 
                       pattern="[0-9]{6}" autocomplete="off"
                       style="letter-spacing: 0.5rem;">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-check-circle"></i> Verifikasi Kode
            </button>
        </form>
        
        <div class="back-link">
            <a href="<?php echo base_url('auth/forgot-password.php'); ?>">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Deteksi waktu dan set warna kode
        function setCodeColor() {
            const codeInput = document.getElementById('code');
            const currentHour = new Date().getHours();
            
            // Pagi: 6:00 - 17:59 (biru)
            // Malam: 18:00 - 5:59 (item/hitam)
            if (currentHour >= 6 && currentHour < 18) {
                // Pagi - biru
                codeInput.classList.remove('night');
                codeInput.classList.add('morning');
            } else {
                // Malam - item/hitam
                codeInput.classList.remove('morning');
                codeInput.classList.add('night');
            }
        }

        // Set warna saat halaman dimuat
        setCodeColor();

        // Auto focus and format code input
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                document.getElementById('verifyForm').submit();
            }
        });
        
        document.getElementById('code').focus();
    </script>
</body>
</html>

