<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

$page_title = 'Kelola Notifikasi Cuaca';
$page_icon = 'bell';
$notificationModel = new Notification($db);
$userModel = new User($db);

$error = '';
$success = '';

// Handle delete
if (isset($_GET['delete'])) {
    $notificationModel->id = $_GET['delete'];
    if ($notificationModel->delete()) {
        $success = 'Notifikasi berhasil dihapus';
    } else {
        $error = 'Gagal menghapus notifikasi';
    }
}

// Get all notifications
$notifications = $notificationModel->getAll(100);
$users = $userModel->getAll();

include '../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo base_url('admin/includes/admin-layout.css'); ?>">

<?php include 'includes/admin-header.php'; ?>
<?php include 'includes/admin-sidebar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="admin-main-content">
            <div class="admin-content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="bi bi-bell"></i>
                        Kelola Notifikasi Cuaca
                    </h2>
                    <a href="<?php echo base_url('admin/notification-form.php'); ?>" class="btn btn-primary btn-action">
                        <i class="bi bi-plus-circle"></i> Tambah Notifikasi
                    </a>
                </div>

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

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>User</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada notifikasi</td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                foreach ($notifications as $notif): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($notif['user_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($notif['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($notif['message'], 0, 50)) . (strlen($notif['message']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($notif['type']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $notif['status'] === 'sent' ? 'success' : 
                                                ($notif['status'] === 'failed' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo htmlspecialchars($notif['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo base_url('admin/notification-form.php?id=' . $notif['id']); ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?php echo base_url('admin/notifications.php?delete=' . $notif['id']); ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus notifikasi ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>

<?php include '../includes/footer.php'; ?>
