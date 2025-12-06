<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Services/NotificationService.php';

// Check login manually to avoid redirect
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Please login first'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$user_id = $_SESSION['user_id'] ?? null;

// Check if user_id is set
if (!$user_id) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'User ID not found in session'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check if database connection is available
if (!isset($db) || !$db) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => 'Database connection not available'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $notificationService = new NotificationService($db);
    $notificationModel = new Notification($db);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Initialization error',
        'message' => 'Failed to initialize notification service'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    switch ($action) {
        case 'list':
            try {
                // Get more notifications to ensure old unread ones are shown
                $notifications = $notificationModel->getByUserId($user_id, 50);
                
                // Ensure clean output
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                
                // Ensure JSON header is set
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                
                echo json_encode([
                    'success' => true,
                    'notifications' => is_array($notifications) ? $notifications : []
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                // Ensure clean output
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                
                error_log("Error in list action: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                // Ensure JSON header is set
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to fetch notifications',
                    'message' => 'An error occurred while fetching notifications'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
        
        case 'unread_count':
            try {
                $count = $notificationService->getUnreadCount($user_id);
                
                // Ensure clean output
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                
                // Ensure JSON header is set
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                
                echo json_encode([
                    'success' => true,
                    'count' => (int)$count
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                // Ensure clean output
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                
                error_log("Error in unread_count action: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                // Ensure JSON header is set
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to get unread count',
                    'count' => 0
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
        
        case 'mark_read':
            $notification_id = $_POST['notification_id'] ?? null;
            if ($notification_id) {
                $success = $notificationService->markAsRead($notification_id, $user_id);
                ob_clean();
                echo json_encode([
                    'success' => $success
                ], JSON_UNESCAPED_UNICODE);
            } else {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Notification ID required'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
        
        case 'mark_all_read':
            try {
                $success = $notificationService->markAllAsRead($user_id);
                ob_clean();
                echo json_encode([
                    'success' => $success
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                ob_clean();
                error_log("Error in mark_all_read: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to mark all as read',
                    'message' => 'An error occurred while updating notifications'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
        
        default:
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => 'An error occurred while processing your request',
        'debug' => ini_get('display_errors') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => 'An error occurred while processing your request',
        'debug' => ini_get('display_errors') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}

