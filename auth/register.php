<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        $user = new User($db);
        
        // Check if email already exists
        if ($user->findByEmail($email)) {
            $error = 'Email sudah terdaftar';
        } else {
            $user->name = $name;
            $user->email = $email;
            $user->password = $password;
            $user->role = 'user';
            
            if ($user->register()) {
                $success = 'Registrasi berhasil! Silakan login.';
                header('refresh:2;url=' . base_url('auth/login.php'));
            } else {
                $error = 'Terjadi kesalahan saat registrasi';
            }
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
    <title>Daftar - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* ===== REGISTER PAGE - BLUE & YELLOW THEME ===== */
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
            padding: 0;
            position: relative;
            overflow-x: hidden;
            transition: background 0.5s ease;
        }

        /* Tema Malam (Hitam) */
        body.night {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            background-size: 400% 400%;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            min-height: 100vh;
        }

        /* Register Card */
        .register-card {
            background: white;
            border-radius: 0;
            overflow: hidden;
            width: 100%;
            min-height: 100vh;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            animation: fadeUp 0.6s ease;
            display: flex;
            flex-direction: column;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header - Blue */
        .register-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 2.5rem 2rem 3rem;
            text-align: left;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 30px 30px;
            flex-shrink: 0;
        }

        .register-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .register-header-close {
            width: 32px;
            height: 32px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .register-header-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .register-header-menu {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.5rem;
        }

        .register-header-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .register-header-illustration {
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            width: 180px;
            height: 180px;
            opacity: 0.9;
            pointer-events: none;
        }

        .register-header-illustration svg {
            width: 100%;
            height: 100%;
        }

        /* Body - White */
        .register-body {
            padding: 2rem 1.5rem;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            background: white;
            -webkit-overflow-scrolling: touch;
        }

        /* Form Labels */
        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .form-label i {
            color: #fbbf24;
            font-size: 1.1rem;
        }

        /* Input Fields */
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
            color: #333;
            width: 100%;
        }

        .form-control:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.15), 0 4px 12px rgba(251, 191, 36, 0.2);
            outline: none;
            background: #fffbeb;
        }

        .form-control::placeholder {
            color: #999;
        }

        /* Checkbox */
        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            border: 2px solid #1e40af;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #1e40af;
            border-color: #1e40af;
        }

        .form-check-label {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .form-check-label strong {
            color: #1e40af;
        }

        /* Primary Button - Blue */
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
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Google Button */
        .btn-google {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: #1e293b;
            padding: 0.875rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1rem;
            text-decoration: none;
            font-stretch: normal;
            letter-spacing: normal;
            font-variant: normal;
        }

        .btn-google:hover {
            background: #f8fafc;
            border-color: #4285f4;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2);
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

        /* Separator */
        .separator {
            text-align: center;
            margin: 1.5rem 0;
            color: #999;
            font-size: 0.9rem;
            position: relative;
        }

        .separator::before,
        .separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e0e0e0;
        }

        .separator::before {
            left: 0;
        }

        .separator::after {
            right: 0;
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
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        /* Links */
        .text-center {
            margin-top: 1.5rem;
        }

        .text-center p {
            color: #666;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .text-center a {
            color: #1e40af;
            font-weight: 700;
            text-decoration: none;
        }

        .text-center a:hover {
            color: #3b82f6;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 576px) {
            html, body {
                overflow-x: hidden;
                width: 100%;
                max-width: 100vw;
            }

            .container {
                max-width: 100%;
                min-height: 100vh;
                height: auto;
            }

            .register-card {
                border-radius: 0;
                min-height: 100vh;
                height: auto;
            }

            .register-header {
                padding: 1.5rem 1.25rem 2rem;
                border-radius: 0 0 20px 20px;
                min-height: auto;
            }

            .register-header-top {
                margin-bottom: 1rem;
            }

            .register-header-title {
                font-size: 1.35rem;
                line-height: 1.4;
                margin-bottom: 0.75rem;
            }

            .register-header-illustration {
                width: 100px;
                height: 100px;
                right: -40px;
                opacity: 0.6;
            }

            .register-body {
                padding: 1.25rem 1rem 2rem;
                min-height: auto;
                flex: 1;
            }

            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
                padding: 0.75rem 0.875rem;
            }

            .form-label {
                font-size: 0.85rem;
            }

            .btn-primary,
            .btn-google {
                padding: 0.75rem;
                font-size: 0.95rem;
            }

            .form-check {
                margin: 1.25rem 0;
                gap: 0.5rem;
            }

            .form-check-label {
                font-size: 0.85rem;
            }

            .separator {
                margin: 1.25rem 0;
            }

            .text-center {
                margin-top: 1.25rem;
            }

            .text-center p {
                font-size: 0.85rem;
            }
        }

        @media (min-width: 577px) {
            body {
                padding: 1rem;
            }

            .container {
                max-height: 90vh;
            }

            .register-card {
                border-radius: 30px;
                max-height: 850px;
                min-height: auto;
            }

            .register-header {
                border-radius: 30px 30px 0 0;
            }

            .register-body {
                max-height: calc(850px - 200px);
                overflow-y: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center">
        <div class="register-card">
            <div class="register-header">
                <div class="register-header-top">
                    <button class="register-header-menu" onclick="window.history.back()">
                        <i class="bi bi-three-dots"></i>
                    </button>
                    <button class="register-header-close" onclick="window.location.href='<?php echo base_url('auth/login.php'); ?>'">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <h3 class="register-header-title">Let's Create<br>Your Account</h3>
                <div class="register-header-illustration">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <!-- Person on bicycle in rain with yellow accents -->
                        <circle cx="100" cy="100" r="80" fill="rgba(255,255,255,0.1)"/>
                        <!-- Bicycle -->
                        <rect x="60" y="120" width="80" height="8" fill="rgba(255,255,255,0.3)" rx="4"/>
                        <circle cx="80" cy="150" r="15" fill="rgba(251,191,36,0.3)" stroke="rgba(251,191,36,0.6)" stroke-width="2"/>
                        <circle cx="120" cy="150" r="15" fill="rgba(251,191,36,0.3)" stroke="rgba(251,191,36,0.6)" stroke-width="2"/>
                        <!-- Person -->
                        <circle cx="100" cy="80" r="12" fill="rgba(255,255,255,0.4)"/>
                        <rect x="90" y="92" width="20" height="30" fill="rgba(59,130,246,0.4)" rx="10"/>
                        <!-- Umbrella with yellow -->
                        <path d="M 100 85 L 100 110 L 85 100 Z" fill="rgba(251,191,36,0.5)"/>
                        <!-- Sun icon -->
                        <circle cx="160" cy="50" r="20" fill="rgba(251,191,36,0.4)"/>
                        <circle cx="160" cy="50" r="15" fill="rgba(251,191,36,0.6)"/>
                        <!-- Rain lines -->
                        <line x1="50" y1="60" x2="50" y2="80" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                        <line x1="70" y1="50" x2="70" y2="70" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                        <line x1="130" y1="55" x2="130" y2="75" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                        <line x1="150" y1="65" x2="150" y2="85" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="bi bi-person"></i> Full Name
                        </label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="Full Name">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i> Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Email Address">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-lock-fill"></i> Retype Password
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Retype Password">
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <strong>Terms & Privacy</strong>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        Sign Up
                    </button>
                </form>
                
                <div class="separator">or</div>
                
                <a href="<?php echo base_url('auth/google-login.php'); ?>" class="btn btn-google">
                    <i class="bi bi-google"></i> 
                    <span>Sign Up with Google</span>
                </a>
                
                <div class="text-center">
                    <p>Have an account? <a href="<?php echo base_url('auth/login.php'); ?>">Sign In</a></p>
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
