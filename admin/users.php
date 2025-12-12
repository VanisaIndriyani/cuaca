<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

$page_title = 'Kelola User';
$page_icon = 'people';
$userModel = new User($db);
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
                        <i class="bi bi-people"></i>
                        Kelola User
                    </h2>
                    <a href="<?php echo base_url('admin/user-form.php'); ?>" class="btn btn-primary btn-action">
                        <i class="bi bi-plus-circle"></i> Tambah User
                    </a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Bergabung</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada user</td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                foreach ($users as $user): 
                                    $isDeactivated = $userModel->isDeactivated($user);
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo htmlspecialchars(ucfirst(displayRole($user['role']))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isDeactivated): ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-person-x"></i> Nonaktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-person-check"></i> Aktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="<?php echo base_url('admin/user-form.php?id=' . $user['id']); ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <?php if ($user['role'] === 'user' && $user['id'] != $_SESSION['user_id']): ?>
                                                <?php if ($isDeactivated): ?>
                                                    <a href="<?php echo base_url('admin/user-reactivate.php?id=' . $user['id']); ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       onclick="return confirm('Apakah Anda yakin ingin mengaktifkan kembali akun ini?')">
                                                        <i class="bi bi-person-check"></i> Aktifkan
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo base_url('admin/user-deactivate.php?id=' . $user['id']); ?>" 
                                                       class="btn btn-sm btn-danger">
                                                        <i class="bi bi-person-x"></i> Nonaktifkan
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="<?php echo base_url('admin/user-delete.php?id=' . $user['id']); ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
                                            <?php endif; ?>
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
