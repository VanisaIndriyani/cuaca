<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

$error = '';

// Check for error from session (e.g., from Google OAuth)
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Check for error from URL parameter
if (isset($_GET['error'])) {
    $error = 'Terjadi kesalahan saat autentikasi. Silakan coba lagi.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        $user = new User($db);
        if ($user->login($email, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_role'] = $user->role;
            if (!empty($user->avatar)) {
                if (strpos($user->avatar, 'http') === 0) {
                    $_SESSION['user_avatar'] = $user->avatar;
                } else {
                    $_SESSION['user_avatar'] = base_url('public' . $user->avatar);
                }
            } else {
                $_SESSION['user_avatar'] = null;
            }
            
            redirectAfterLogin();
        } else {
            $error = 'Email atau password salah';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirectAfterLogin();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* ===== LOGIN PAGE - BLUE YELLOW WHITE THEME ===== */
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
            background: radial-gradient(circle, rgba(255, 235, 59, 0.15) 0%, transparent 70%);
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

        /* Login Card */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5);
            animation: fadeUp 0.6s ease;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header i {
            font-size: 4rem;
            color: #fbbf24;
            text-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
            margin-bottom: 1rem;
            display: block;
            animation: sunGlow 3s ease-in-out infinite;
        }

        @keyframes sunGlow {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.5)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 20px rgba(251, 191, 36, 0.8)); }
        }

        .login-header h3 {
            color: #1e40af;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
        }

        /* Form Labels */
        .form-label {
            color: #1e40af;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #fbbf24;
            font-size: 1.1rem;
        }

        /* Input Fields */
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.875rem 1.125rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #ffffff;
            color: #1e293b;
        }

        .form-control:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.15), 0 4px 12px rgba(251, 191, 36, 0.2);
            outline: none;
            background: #fffbeb;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        /* Primary Button */
        .btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border: none;
            color: #ffffff;
            padding: 0.875rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.5);
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary span {
            position: relative;
            z-index: 1;
        }

        /* Google Button */
        .btn-google {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: #1e293b;
            padding: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-stretch: normal;
            letter-spacing: normal;
            font-variant: normal;
        }

        .btn-google:hover {
            background: #f8fafc;
            border-color: #fbbf24;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
            color: #1e293b;
        }

        .btn-google i {
            font-size: 1.2rem;
            color: #ea4335;
        }

        .btn-google span {
            font-stretch: normal;
            letter-spacing: normal;
            font-variant: normal;
        }

        /* Alert */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        /* Links */
        .text-center {
            margin-top: 1.5rem;
        }

        .text-center p {
            color: #64748b;
            margin-bottom: 0;
        }

        .text-center a {
            color: #1e40af;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .text-center a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #fbbf24;
            transition: width 0.3s ease;
        }

        .text-center a:hover {
            color: #3b82f6;
        }

        .text-center a:hover::after {
            width: 100%;
        }

        /* Login Body */
        .login-body {
            margin-top: 1rem;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }

            .login-header i {
                font-size: 3rem;
            }

            .login-header h3 {
                font-size: 1.5rem;
            }

            body::before,
            body::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-cloud-sun weather-icon"></i>
                <h3><?php echo APP_NAME; ?></h3>
                <p class="mb-0">Masuk ke akun Anda</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="background: rgba(220, 53, 69, 0.9); border: 2px solid rgba(220, 53, 69, 0.5); border-radius: 12px; color: white; padding: 0.875rem 1rem; margin-bottom: 1.5rem; backdrop-filter: blur(10px);">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i> Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Masukkan email Anda">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password Anda">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <span><i class="bi bi-box-arrow-in-right"></i> Masuk</span>
                    </button>
                </form>
                
                <a href="<?php echo base_url('auth/google-login.php'); ?>" class="btn btn-google w-100 mb-3 text-decoration-none">
                    <i class="bi bi-google"></i> 
                    <span>Masuk dengan Google</span>
                </a>
                
                <div class="text-center">
                    <p class="mb-2">Belum punya akun? <a href="<?php echo base_url('auth/register.php'); ?>">Daftar</a></p>
                    <p class="mb-0">
                        <a href="<?php echo base_url('auth/forgot-password.php'); ?>" style="color: #667eea; text-decoration: none;">
                            <i class="bi bi-question-circle"></i> Lupa Password?
                        </a>
                    </p>
                </div>
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
