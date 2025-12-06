<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Activity.php';

requireAdmin();

$id = $_GET['id'] ?? 0;

$activity = new Activity($db);
$activity->id = $id;

if ($activity->delete()) {
    $_SESSION['success'] = 'Aktivitas berhasil dihapus';
} else {
    $_SESSION['error'] = 'Gagal menghapus aktivitas';
}

redirect('admin/activities.php');

