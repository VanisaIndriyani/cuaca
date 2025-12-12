<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

$userModel = new User($db);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $duration = $_POST['duration'] ?? '1_day';
    
    if (empty($user_id)) {
        $error = 'User ID tidak valid';
    } else {
        $user = $userModel->getById($user_id);
        
        if (!$user) {
            $error = 'User tidak ditemukan';
        } else if ($user['role'] === 'admin') {
            $error = 'Tidak dapat menonaktifkan akun admin';
        } else if ($user['id'] == $_SESSION['user_id']) {
            $error = 'Tidak dapat menonaktifkan akun sendiri';
        } else {
            if ($userModel->deactivate($user_id, $duration)) {
                $duration_text = [
                    '1_day' => '1 hari',
                    '3_days' => '3 hari',
                    '7_days' => '7 hari',
                    '30_days' => '30 hari',
                    'permanent' => 'selamanya'
                ];
                $success = 'Akun guest berhasil dinonaktifkan selama ' . ($duration_text[$duration] ?? $duration);
                $_SESSION['success'] = $success;
                redirect('admin/users.php');
            } else {
                $error = 'Gagal menonaktifkan akun';
            }
        }
    }
}

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

if ($user['role'] === 'admin') {
    $_SESSION['error'] = 'Tidak dapat menonaktifkan akun admin';
    redirect('admin/users.php');
}

if ($user['id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = 'Tidak dapat menonaktifkan akun sendiri';
    redirect('admin/users.php');
}

$page_title = 'Nonaktifkan Akun Guest';
$page_icon = 'person-x';

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
                        <i class="bi bi-person-x"></i>
                        Nonaktifkan Akun Guest
                    </h2>
                    <a href="<?php echo base_url('admin/users.php'); ?>" class="btn btn-secondary btn-action">
                        <i class="bi bi-arrow-left"></i> Kembali
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

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Peringatan:</strong> Anda akan menonaktifkan akun guest berikut:
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Informasi User</h5>
                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Role:</strong> 
                            <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst(displayRole($user['role']))); ?></span>
                        </p>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                    
                    <div class="mb-3">
                        <label for="duration" class="form-label">
                            <i class="bi bi-clock"></i> Durasi Nonaktifkan
                        </label>
                        <select class="form-select" id="duration" name="duration" required>
                            <option value="1_day" selected>1 Hari</option>
                            <option value="3_days">3 Hari</option>
                            <option value="7_days">7 Hari</option>
                            <option value="30_days">30 Hari</option>
                            <option value="permanent">Selamanya</option>
                        </select>
                        <small class="form-text text-muted">
                            Pilih berapa lama akun ini akan dinonaktifkan. Guest akan menerima notifikasi saat mencoba login.
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Catatan:</strong> Saat guest mencoba login, mereka akan melihat notifikasi: 
                        "Akun Anda telah dinonaktifkan [durasi] karena melanggar aturan yang ada."
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-person-x"></i> Nonaktifkan Akun
                        </button>
                        <a href="<?php echo base_url('admin/users.php'); ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>

<?php include '../includes/footer.php'; ?>

