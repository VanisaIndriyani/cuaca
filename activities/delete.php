<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Activity.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;

$activity = new Activity($db);
$act = $activity->getById($id);

if (!$act) {
    redirect('activities/index.php');
}

// Check ownership (unless admin)
if (!isAdmin() && $act['user_id'] != $user_id) {
    redirect('activities/index.php');
}

$activity->id = $id;
if ($activity->delete()) {
    $_SESSION['success'] = 'Aktivitas berhasil dihapus';
} else {
    $_SESSION['error'] = 'Gagal menghapus aktivitas';
}

redirect('activities/index.php');

