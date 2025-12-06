<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Models/Notification.php';
require_once __DIR__ . '/ApiClientWeather.php';

class NotificationService {
    private $conn;
    private $notification;
    private $vapid_public_key;
    private $vapid_private_key;
    private $vapid_subject;
    private $apiClient;

    public function __construct($db) {
        $this->conn = $db;
        $this->notification = new Notification($db);
        $this->apiClient = new ApiClientWeather();
        $this->loadVapidKeys();
    }

    private function loadVapidKeys() {
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
        
        $this->vapid_public_key = $_ENV['VAPID_PUBLIC_KEY'] ?? '';
        $this->vapid_private_key = $_ENV['VAPID_PRIVATE_KEY'] ?? '';
        $this->vapid_subject = $_ENV['VAPID_SUBJECT'] ?? 'mailto:admin@example.com';
    }

    public function sendPushNotification($user_id, $title, $message, $type = 'info') {
        // Get user's push subscription
        $query = "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $subscriptions = $stmt->fetchAll();

        if (empty($subscriptions)) {
            // Log as failed if no subscription
            $this->notification->user_id = $user_id;
            $this->notification->title = $title;
            $this->notification->message = $message;
            $this->notification->type = $type;
            $this->notification->status = 'failed';
            $this->notification->create();
            return false;
        }

        $success = false;
        foreach ($subscriptions as $sub) {
            if ($this->sendPush($sub['endpoint'], $sub['p256dh'], $sub['auth'], $title, $message)) {
                $success = true;
            }
        }

        // Log notification
        $this->notification->user_id = $user_id;
        $this->notification->title = $title;
        $this->notification->message = $message;
        $this->notification->type = $type;
        $this->notification->status = $success ? 'sent' : 'failed';
        $this->notification->create();

        if ($success) {
            $this->notification->markAsSent($this->notification->id);
        } else {
            $this->notification->markAsFailed($this->notification->id);
        }

        return $success;
    }

    private function sendPush($endpoint, $p256dh, $auth, $title, $message) {
        // This is a simplified version. In production, use a library like web-push-php
        // For now, we'll just log it
        error_log("Push notification to: $endpoint");
        return true; // Simplified - implement actual push in production
    }

    public function subscribe($user_id, $subscription) {
        $query = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) 
                  VALUES (:user_id, :endpoint, :p256dh, :auth)
                  ON DUPLICATE KEY UPDATE 
                  endpoint = VALUES(endpoint), 
                  p256dh = VALUES(p256dh), 
                  auth = VALUES(auth)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':endpoint', $subscription['endpoint']);
        $stmt->bindParam(':p256dh', $subscription['keys']['p256dh']);
        $stmt->bindParam(':auth', $subscription['keys']['auth']);
        
        return $stmt->execute();
    }

    /**
     * Check and send rain warning notification
     * Rule: If rain probability > 70%
     */
    public function checkRainWarning($user_id, $location) {
        $forecast = $this->apiClient->fetchForecast($location, 1);
        
        if (!$forecast || !isset($forecast['list'])) {
            return false;
        }

        // Check today's forecast
        $today_forecast = $forecast['list'][0] ?? null;
        if (!$today_forecast) {
            return false;
        }

        $rain_probability = $today_forecast['pop'] * 100 ?? 0; // pop is 0-1, convert to percentage
        
        if ($rain_probability > 70) {
            $title = "⚠️ Peringatan Hujan";
            $message = "Hari ini kemungkinan hujan " . round($rain_probability) . "% di " . $location . ". Jangan lupa bawa payung!";
            
            // Check if notification already sent today
            if (!$this->isNotificationSentToday($user_id, 'rain_warning', $location)) {
                $this->sendPushNotification($user_id, $title, $message, 'warning');
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check and send extreme temperature notification
     * Rule: If max temperature > 34°C
     */
    public function checkExtremeTemperature($user_id, $location) {
        $weather = $this->apiClient->fetchCurrentWeather($location);
        
        if (!$weather || !isset($weather['main'])) {
            return false;
        }

        $max_temp = $weather['main']['temp_max'] ?? $weather['main']['temp'];
        
        if ($max_temp > 34) {
            $title = "🔥 Suhu Ekstrem";
            $message = "Suhu di " . $location . " bisa sampai " . round($max_temp) . "°C hari ini. Minum air yang cukup ya!";
            
            // Check if notification already sent today
            if (!$this->isNotificationSentToday($user_id, 'extreme_temp', $location)) {
                $this->sendPushNotification($user_id, $title, $message, 'warning');
                return true;
            }
        }
        
        return false;
    }

    /**
     * Send daily forecast notification
     * Rule: Send every day at 06:00
     */
    public function sendDailyForecast($user_id, $location) {
        $weather = $this->apiClient->fetchCurrentWeather($location);
        $forecast = $this->apiClient->fetchForecast($location, 1);
        
        if (!$weather || !isset($weather['main']) || !isset($weather['weather'][0])) {
            return false;
        }

        $condition = $weather['weather'][0]['description'] ?? 'berawan';
        $temp_min = $weather['main']['temp_min'] ?? $weather['main']['temp'];
        $temp_max = $weather['main']['temp_max'] ?? $weather['main']['temp'];
        $rain_probability = 0;
        
        if ($forecast && isset($forecast['list'][0])) {
            $rain_probability = ($forecast['list'][0]['pop'] ?? 0) * 100;
        }

        $current_hour = (int)date('H');
        $greeting = 'Selamat pagi';
        if ($current_hour >= 12 && $current_hour < 18) {
            $greeting = 'Selamat siang';
        } elseif ($current_hour >= 18) {
            $greeting = 'Selamat malam';
        }

        $title = "📅 Cuaca Hari Ini";
        $message = $greeting . "! Cuaca " . $location . " hari ini: " . $condition . ", " . round($temp_min) . "–" . round($temp_max) . "°C, kemungkinan hujan " . round($rain_probability) . "%.";
        
        // Check if notification already sent today
        if (!$this->isNotificationSentToday($user_id, 'daily_forecast', $location)) {
            $this->sendPushNotification($user_id, $title, $message, 'info');
            return true;
        }
        
        return false;
    }

    /**
     * Check if notification of specific type already sent today
     */
    private function isNotificationSentToday($user_id, $type, $location = null) {
        $query = "SELECT COUNT(*) as count FROM notifications 
                  WHERE user_id = :user_id 
                  AND type = :type 
                  AND DATE(created_at) = CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }

    /**
     * Get unread notification count for user
     */
    public function getUnreadCount($user_id) {
        try {
            // Count all notifications that are not read, regardless of status
            // This includes 'sent' and 'pending' notifications
            // read_at IS NULL means not read yet
            $query = "SELECT COUNT(*) as count FROM notifications 
                      WHERE user_id = :user_id 
                      AND (status = 'sent' OR status = 'pending')
                      AND read_at IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $count = isset($result['count']) ? (int)$result['count'] : 0;
            return $count;
        } catch (Exception $e) {
            error_log("Error in getUnreadCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $query = "UPDATE notifications 
                      SET read_at = NOW() 
                      WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            return $result !== false;
        } catch (Exception $e) {
            error_log("Error in markAsRead: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($user_id) {
        try {
            if (!$user_id) {
                error_log("markAllAsRead: user_id is empty");
                return false;
            }
            
            $query = "UPDATE notifications 
                      SET read_at = NOW() 
                      WHERE user_id = :user_id 
                      AND read_at IS NULL";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                error_log("markAllAsRead prepare error: " . print_r($error, true));
                return false;
            }
            
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if (!$result) {
                $error = $stmt->errorInfo();
                error_log("markAllAsRead execute error: " . print_r($error, true));
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in markAllAsRead: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Error $e) {
            error_log("Fatal error in markAllAsRead: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
}

