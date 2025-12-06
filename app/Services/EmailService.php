<?php
require_once __DIR__ . '/../../config/config.php';

// Load PHPMailer via Composer autoload
$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $smtp_from;
    private $smtp_from_name;
    private $use_phpmailer;
    
    public function __construct() {
        // Load email config from .env
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
        
        $this->smtp_host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtp_port = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->smtp_user = $_ENV['SMTP_USER'] ?? '';
        $this->smtp_pass = $_ENV['SMTP_PASS'] ?? '';
        $this->smtp_from = $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USER'] ?? 'noreply@cuaca.app';
        $this->smtp_from_name = $_ENV['SMTP_FROM_NAME'] ?? APP_NAME;
        
        // Check if PHPMailer is available
        $this->use_phpmailer = class_exists('PHPMailer\PHPMailer\PHPMailer') && !empty($this->smtp_user) && !empty($this->smtp_pass);
        
        // Log configuration status for debugging
        $is_production = !empty($_ENV['APP_URL']) && strpos($_ENV['APP_URL'], 'localhost') === false && strpos($_ENV['APP_URL'], '127.0.0.1') === false;
        
        // Always log configuration issues in production for debugging
        if (!$this->use_phpmailer) {
            $log_msg = "EmailService Config Issue - ";
            $log_msg .= "PHPMailer: " . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? 'Yes' : 'No') . ", ";
            $log_msg .= "SMTP_USER: " . (!empty($this->smtp_user) ? 'Set' : 'Empty') . ", ";
            $log_msg .= "SMTP_PASS: " . (!empty($this->smtp_pass) ? 'Set' : 'Empty') . ", ";
            $log_msg .= "ENV File: " . (file_exists($envFile) ? 'Exists' : 'Not Found');
            error_log($log_msg);
        }
        
        // Log successful configuration in production (for troubleshooting)
        if ($is_production && $this->use_phpmailer) {
            error_log("EmailService: SMTP configured for production - Host: {$this->smtp_host}, Port: {$this->smtp_port}, User: " . substr($this->smtp_user, 0, 5) . "...");
        }
    }
    
    /**
     * Send email using PHPMailer (if available) or PHP mail() function
     */
    public function sendEmail($to, $subject, $message, $isHTML = true) {
        // Use PHPMailer if available and configured
        if ($this->use_phpmailer) {
            return $this->sendEmailWithPHPMailer($to, $subject, $message, $isHTML);
        }
        
        // Fallback to PHP mail() function
        error_log("Using PHP mail() fallback. PHPMailer not available or SMTP not configured.");
        return $this->sendEmailWithMail($to, $subject, $message, $isHTML);
    }
    
    /**
     * Send email using PHPMailer with SMTP
     */
    private function sendEmailWithPHPMailer($to, $subject, $message, $isHTML = true) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_user;
            $mail->Password = $this->smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use STARTTLS
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Enable verbose debug output for local development
            // But redirect to error_log to avoid "headers already sent" warning
            $is_production = !empty($_ENV['APP_URL']) && strpos($_ENV['APP_URL'], 'localhost') === false && strpos($_ENV['APP_URL'], '127.0.0.1') === false;
            if (!$is_production) {
                // Only enable debug if we're in test-smtp.php (where output is expected)
                $is_test_page = strpos($_SERVER['PHP_SELF'], 'test-smtp.php') !== false;
                if ($is_test_page) {
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable debug for test page
                } else {
                    // For other pages, log to error_log instead of outputting
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                    $mail->Debugoutput = function($str, $level) {
                        error_log("PHPMailer SMTP Debug: $str");
                    };
                }
            } else {
                $mail->SMTPDebug = 0; // Disable debug for production
            }
            
            // Recipients
            $mail->setFrom($this->smtp_from, $this->smtp_from_name);
            $mail->addAddress($to);
            $mail->addReplyTo($this->smtp_from, $this->smtp_from_name);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if (!$isHTML) {
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            error_log("Email sent successfully to: $to");
            return true;
            
        } catch (Exception $e) {
            $error_info = isset($mail) ? $mail->ErrorInfo : 'PHPMailer not initialized';
            $error_msg = "PHPMailer Error: " . $error_info;
            $error_msg .= " | Exception: " . $e->getMessage();
            $error_msg .= " | SMTP Config - Host: {$this->smtp_host}, Port: {$this->smtp_port}";
            $error_msg .= " | User: " . (!empty($this->smtp_user) ? substr($this->smtp_user, 0, 10) . '...' : 'Empty');
            $error_msg .= " | Pass: " . (!empty($this->smtp_pass) ? 'Set (' . strlen($this->smtp_pass) . ' chars)' : 'Empty');
            
            error_log($error_msg);
            error_log("Failed to send email to: $to");
            
            // Check for specific authentication errors
            if (strpos($error_info, '535') !== false || strpos($error_info, 'BadCredentials') !== false || strpos($error_info, 'Could not authenticate') !== false) {
                error_log("AUTHENTICATION ERROR: Pastikan menggunakan App Password (bukan password biasa) dan 2-Step Verification sudah aktif");
            }
            
            // Log to file for production debugging
            $is_production = !empty($_ENV['APP_URL']) && strpos($_ENV['APP_URL'], 'localhost') === false && strpos($_ENV['APP_URL'], '127.0.0.1') === false;
            if ($is_production) {
                $log_file = __DIR__ . '/../../logs/email_errors.log';
                $log_dir = dirname($log_file);
                if (!is_dir($log_dir)) {
                    @mkdir($log_dir, 0755, true);
                }
                @file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $error_msg . "\n", FILE_APPEND);
            }
            
            return false;
        }
    }
    
    /**
     * Send email using PHP mail() function (fallback)
     */
    private function sendEmailWithMail($to, $subject, $message, $isHTML = true) {
        $headers = [];
        $headers[] = 'From: ' . $this->smtp_from_name . ' <' . $this->smtp_from . '>';
        $headers[] = 'Reply-To: ' . $this->smtp_from;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        if ($isHTML) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }
        
        $headers_string = implode("\r\n", $headers);
        
        // Try to send email
        $result = @mail($to, $subject, $message, $headers_string);
        
        if (!$result) {
            error_log("Failed to send email to: $to using mail() function");
            return false;
        }
        
        return true;
    }
    
    /**
     * Send password reset code email
     */
    public function sendPasswordResetCode($to, $code, $name = 'User') {
        $subject = 'Kode Reset Password - ' . APP_NAME;
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
                .code-box { background: white; border: 2px dashed #3b82f6; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .code { font-size: 32px; font-weight: bold; color: #3b82f6; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #6b7280; font-size: 12px; }
                .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . htmlspecialchars(APP_NAME) . '</h1>
                    <p>Reset Password</p>
                </div>
                <div class="content">
                    <p>Halo <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>Kami menerima permintaan untuk mereset password akun Anda. Gunakan kode berikut untuk melanjutkan:</p>
                    
                    <div class="code-box">
                        <div class="code">' . htmlspecialchars($code) . '</div>
                    </div>
                    
                    <div class="warning">
                        <strong>⚠️ Penting:</strong>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Kode ini hanya berlaku selama <strong>15 menit</strong></li>
                            <li>Jangan bagikan kode ini kepada siapapun</li>
                            <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                        </ul>
                    </div>
                    
                    <p>Jika Anda tidak meminta reset password, silakan abaikan email ini.</p>
                    
                    <p>Terima kasih,<br><strong>Tim ' . htmlspecialchars(APP_NAME) . '</strong></p>
                </div>
                <div class="footer">
                    <p>Email ini dikirim secara otomatis, mohon jangan membalas email ini.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $this->sendEmail($to, $subject, $message, true);
    }
}

