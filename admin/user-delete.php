<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

$id = $_GET['id'] ?? 0;

if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'Tidak dapat menghapus akun sendiri';
    redirect('admin/users.php');
}

$user = new User($db);
$user->id = $id;

if ($user->delete()) {
    $_SESSION['success'] = 'User berhasil dihapus';
} else {
    $_SESSION['error'] = 'Gagal menghapus user';
}

redirect('admin/users.php');

