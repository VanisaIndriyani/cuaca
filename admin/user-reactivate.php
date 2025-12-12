<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

$userModel = new User($db);

$user_id = $_GET['id'] ?? 0;
if (empty($user_id)) {
    $_SESSION['error'] = 'User ID tidak valid';
    redirect('admin/users.php');
}

$user = $userModel->getById($user_id);
if (!$user) {
    $_SESSION['error'] = 'User tidak ditemukan';
    redirect('admin/users.php');
}

if ($userModel->reactivate($user_id)) {
    $_SESSION['success'] = 'Akun guest berhasil diaktifkan kembali';
} else {
    $_SESSION['error'] = 'Gagal mengaktifkan kembali akun';
}

redirect('admin/users.php');

