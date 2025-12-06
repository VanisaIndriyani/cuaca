<?php
// Template untuk halaman admin - Copy struktur ini ke semua halaman admin

require_once __DIR__ . '/../config/config.php';
// Include models yang diperlukan
require_once __DIR__ . '/../app/Models/User.php';

requireAdmin();

// Set page title dan icon
$page_title = 'Nama Halaman';
$page_icon = 'gear'; // Icon untuk header mobile

// Logic halaman di sini
$error = '';
$success = '';

include '../includes/header.php';
?>

<!-- Include Admin Layout CSS -->
<link rel="stylesheet" href="<?php echo base_url('admin/includes/admin-layout.css'); ?>">

<!-- Custom styles untuk halaman ini (jika ada) -->
<style>
/* Custom styles untuk halaman ini */
</style>

<!-- Include Admin Header & Sidebar -->
<?php include 'includes/admin-header.php'; ?>
<?php include 'includes/admin-sidebar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="admin-main-content">
            <div class="admin-content-card">
                <h2>
                    <i class="bi bi-<?php echo $page_icon; ?>"></i>
                    <?php echo htmlspecialchars($page_title); ?>
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

                <!-- Content halaman di sini -->
                <p>Content halaman...</p>
            </div>
        </main>
    </div>
</div>

<!-- Include Admin Sidebar JS -->
<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>

<?php include '../includes/footer.php'; ?>

