<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

$page_title = isset($_GET['id']) ? 'Edit Notifikasi' : 'Tambah Notifikasi';
$page_icon = isset($_GET['id']) ? 'pencil' : 'plus-circle';
$notificationModel = new Notification($db);
$userModel = new User($db);

$error = '';
$success = '';
$notification = null;
$is_edit = false;

// Get notification if editing
if (isset($_GET['id'])) {
    $is_edit = true;
    $notification = $notificationModel->getById($_GET['id']);
    if (!$notification) {
        $error = 'Notifikasi tidak ditemukan';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';
    $type = $_POST['type'] ?? 'info';
    $status = $_POST['status'] ?? 'sent'; // Default 'sent' agar langsung terhitung sebagai unread
    
    if (empty($user_id) || empty($title) || empty($message)) {
        $error = 'Semua field harus diisi';
    } else {
        $notificationModel->user_id = $user_id;
        $notificationModel->title = $title;
        $notificationModel->message = $message;
        $notificationModel->type = $type;
        $notificationModel->status = $status;
        
        if ($is_edit && isset($_POST['id'])) {
            $notificationModel->id = $_POST['id'];
            if ($notificationModel->update()) {
                $success = 'Notifikasi berhasil diupdate';
                $notification = $notificationModel->getById($_POST['id']);
            } else {
                $error = 'Gagal mengupdate notifikasi';
            }
        } else {
            if ($notificationModel->create()) {
                $success = 'Notifikasi berhasil ditambahkan';
                header('refresh:1;url=' . base_url('admin/notifications.php'));
            } else {
                $error = 'Gagal menambahkan notifikasi';
            }
        }
    }
}

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
                <h2>
                    <i class="bi bi-<?php echo $is_edit ? 'pencil' : 'plus-circle'; ?>"></i>
                    <?php echo $is_edit ? 'Edit Notifikasi' : 'Tambah Notifikasi'; ?>
                </h2>

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
                    <?php if ($is_edit && $notification): ?>
                        <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="user_id" class="form-label">
                            <i class="bi bi-person"></i> User
                        </label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Pilih User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                    <?php echo ($notification && $notification['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">
                            <i class="bi bi-type"></i> Title
                        </label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo $notification ? htmlspecialchars($notification['title']) : ''; ?>" 
                               required placeholder="Masukkan title notifikasi">
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">
                            <i class="bi bi-chat-text"></i> Message
                        </label>
                        <textarea class="form-control" id="message" name="message" rows="5" 
                                  required placeholder="Masukkan pesan notifikasi"><?php echo $notification ? htmlspecialchars($notification['message']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">
                            <i class="bi bi-tag"></i> Type
                        </label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="info" <?php echo ($notification && $notification['type'] == 'info') ? 'selected' : ''; ?>>Info</option>
                            <option value="warning" <?php echo ($notification && $notification['type'] == 'warning') ? 'selected' : ''; ?>>Warning</option>
                            <option value="success" <?php echo ($notification && $notification['type'] == 'success') ? 'selected' : ''; ?>>Success</option>
                            <option value="danger" <?php echo ($notification && $notification['type'] == 'danger') ? 'selected' : ''; ?>>Danger</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">
                            <i class="bi bi-check-circle"></i> Status
                        </label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="sent" <?php echo ($notification && $notification['status'] == 'sent') ? 'selected' : (!isset($notification) ? 'selected' : ''); ?>>Sent</option>
                            <option value="pending" <?php echo ($notification && $notification['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo ($notification && $notification['status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?php echo $is_edit ? 'check' : 'plus'; ?>"></i>
                            <?php echo $is_edit ? 'Update' : 'Simpan'; ?>
                        </button>
                        <a href="<?php echo base_url('admin/notifications.php'); ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>

<?php include '../includes/footer.php'; ?>

