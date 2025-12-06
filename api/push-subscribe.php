<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Services/NotificationService.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['subscription'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Subscription data required']);
    exit;
}

$notificationService = new NotificationService($db);
$user_id = $_SESSION['user_id'];

if ($notificationService->subscribe($user_id, $data['subscription'])) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to subscribe']);
}

