<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

$page_title = isset($_GET['id']) ? 'Edit User' : 'Tambah User';
$page_icon = isset($_GET['id']) ? 'pencil' : 'plus-circle';
$userModel = new User($db);

$error = '';
$success = '';
$user = null;
$is_edit = false;
$is_main_admin = false; // Flag untuk menandai akun admin utama

// Get user if editing
if (isset($_GET['id'])) {
    $is_edit = true;
    $user = $userModel->getById($_GET['id']);
    if (!$user) {
        $error = 'User tidak ditemukan';
    } else {
        // Cek apakah ini adalah admin utama (admin@cuaca.app)
        $is_main_admin = ($user['email'] === 'admin@cuaca.app');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if (empty($name) || empty($email)) {
        $error = 'Nama dan email harus diisi';
    } else {
        // Cek apakah ini adalah admin utama yang sedang diedit
        $editing_main_admin = false;
        if ($is_edit && isset($_POST['id'])) {
            $editing_user = $userModel->getById($_POST['id']);
            if ($editing_user && $editing_user['email'] === 'admin@cuaca.app') {
                $editing_main_admin = true;
                // Paksa role tetap admin untuk admin utama
                $role = 'admin';
                // Paksa email tetap admin@cuaca.app untuk admin utama
                $email = 'admin@cuaca.app';
            }
        }
        
        // Validasi: Admin utama tidak boleh diubah rolenya atau emailnya
        if ($editing_main_admin) {
            if ($role !== 'admin') {
                $error = 'Role akun admin utama (admin@cuaca.app) tidak dapat diubah';
            } elseif ($email !== 'admin@cuaca.app') {
                $error = 'Email akun admin utama tidak dapat diubah';
            }
        }
        
        if (empty($error)) {
            $userModel->name = $name;
            $userModel->email = $email;
            $userModel->role = $role;
            
            if ($is_edit && isset($_POST['id'])) {
                $userModel->id = $_POST['id'];
                
                // Update password only if provided
                if (!empty($password)) {
                    $userModel->password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET name = :name, email = :email, password = :password, role = :role, updated_at = NOW() WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':name', $userModel->name);
                    $stmt->bindParam(':email', $userModel->email);
                    $stmt->bindParam(':password', $userModel->password);
                    $stmt->bindParam(':role', $userModel->role);
                    $stmt->bindParam(':id', $userModel->id);
                    
                    if ($stmt->execute()) {
                        $success = 'User berhasil diupdate';
                        $user = $userModel->getById($_POST['id']);
                        $is_main_admin = ($user['email'] === 'admin@cuaca.app');
                    } else {
                        $error = 'Gagal mengupdate user';
                    }
                } else {
                    // Update without password
                    $query = "UPDATE users SET name = :name, email = :email, role = :role, updated_at = NOW() WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':name', $userModel->name);
                    $stmt->bindParam(':email', $userModel->email);
                    $stmt->bindParam(':role', $userModel->role);
                    $stmt->bindParam(':id', $userModel->id);
                    
                    if ($stmt->execute()) {
                        $success = 'User berhasil diupdate';
                        $user = $userModel->getById($_POST['id']);
                        $is_main_admin = ($user['email'] === 'admin@cuaca.app');
                    } else {
                        $error = 'Gagal mengupdate user';
                    }
                }
            } else {
                // Create new user
                if (empty($password)) {
                    $error = 'Password harus diisi untuk user baru';
                } else {
                    $userModel->password = password_hash($password, PASSWORD_DEFAULT);
                    if ($userModel->register()) {
                        $success = 'User berhasil ditambahkan';
                        header('refresh:1;url=' . base_url('admin/users.php'));
                    } else {
                        $error = 'Gagal menambahkan user. Email mungkin sudah digunakan.';
                    }
                }
            }
        }
    }
}

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
                    <?php echo $is_edit ? 'Edit User' : 'Tambah User'; ?>
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
                    <?php if ($is_edit && $user): ?>
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="bi bi-person"></i> Nama
                        </label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?php echo $user ? htmlspecialchars($user['name']) : ''; ?>"
                               required placeholder="Masukkan nama user">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i> Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>"
                               required placeholder="Masukkan email user"
                               <?php echo $is_main_admin ? 'readonly' : ''; ?>>
                        <?php if ($is_main_admin): ?>
                            <small class="form-text text-warning">
                                <i class="bi bi-info-circle"></i> Email admin utama tidak dapat diubah
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               <?php echo $is_edit ? '' : 'required'; ?>
                               placeholder="<?php echo $is_edit ? 'Kosongkan jika tidak ingin mengubah password' : 'Masukkan password'; ?>">
                        <?php if ($is_edit): ?>
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">
                            <i class="bi bi-shield-check"></i> Role
                        </label>
                        <select class="form-select" id="role" name="role" required <?php echo $is_main_admin ? 'disabled' : ''; ?>>
                            <option value="user" <?php echo ($user && $user['role'] == 'user') ? 'selected' : ''; ?>>Guest</option>
                            <option value="admin" <?php echo ($user && $user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        <?php if ($is_main_admin): ?>
                            <input type="hidden" name="role" value="admin">
                            <small class="form-text text-warning">
                                <i class="bi bi-shield-lock"></i> Role akun admin utama tidak dapat diubah
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?php echo $is_edit ? 'check' : 'plus'; ?>"></i>
                            <?php echo $is_edit ? 'Update' : 'Simpan'; ?>
                        </button>
                        <a href="<?php echo base_url('admin/users.php'); ?>" class="btn btn-secondary">
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

