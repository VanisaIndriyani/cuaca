<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Activity.php';

requireLogin();

$page_title = 'Tambah Aktivitas';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity = new Activity($db);
    $activity->user_id = $user_id;
    $activity->title = $_POST['title'] ?? '';
    $activity->description = $_POST['description'] ?? '';
    $activity->category = $_POST['category'] ?? '';
    $activity->activity_date = $_POST['activity_date'] ?? date('Y-m-d');
    $activity->start_time = $_POST['start_time'] ?? '';
    $activity->end_time = $_POST['end_time'] ?? '';
    $activity->location = $_POST['location'] ?? '';

    if (empty($activity->title) || empty($activity->category)) {
        $error = 'Judul dan kategori harus diisi';
    } else {
        if ($activity->create()) {
            $success = 'Aktivitas berhasil ditambahkan';
            header('refresh:1;url=' . base_url('activities/index.php'));
        } else {
            $error = 'Gagal menambahkan aktivitas';
        }
    }
}

include '../includes/header.php';
?>

<style>
.mobile-header-form {
    display: none;
    background: var(--primary-color);
    color: white;
    padding: 1rem;
    position: sticky;
    top: 0;
    z-index: 999;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mobile-header-form h4 {
    margin: 0;
    font-size: 1.1rem;
}

.mobile-menu-form {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--card-bg);
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    padding: 0.75rem 0;
}

.mobile-menu-items {
    display: flex;
    justify-content: space-around;
    align-items: center;
}

.mobile-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    text-decoration: none;
    color: var(--text-muted);
    font-size: 0.75rem;
    transition: all 0.3s;
    padding: 0.5rem;
    border-radius: 8px;
    min-width: 60px;
}

.mobile-menu-item i {
    font-size: 1.25rem;
}

.mobile-menu-item.active {
    color: var(--primary-color);
    background: rgba(59, 130, 246, 0.1);
}

@media (min-width: 769px) {
    .mobile-header-form,
    .mobile-menu-form {
        display: none !important;
    }
    
    .container {
        max-width: 100%;
        overflow-x: hidden;
        padding: 0;
    }
    
    .row {
        margin: 0;
        display: flex;
    }
    
    .activity-form-content {
        flex: 1;
        margin-left: 80px;
        margin-top: 56px;
        width: calc(100% - 80px);
        max-width: calc(100% - 80px);
        padding: 2rem;
        padding-top: 2.5rem;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .card {
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        border: none;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .card-header {
        background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-bottom: none;
        border-radius: 20px 20px 0 0;
    }
    
    .card-header h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .card-body {
        padding: 2rem;
    }
    
    .form-control,
    .form-select {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        outline: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }
    
    .btn-secondary {
        border: 2px solid #e5e7eb;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: #f3f4f6;
        transform: translateY(-2px);
    }
}

/* Laptop specific styles (1024px - 1399px) */
@media (min-width: 1024px) and (max-width: 1399px) {
    .activity-form-content {
        padding-left: 2rem;
        padding-right: 2rem;
        padding-top: 2.5rem;
    }
    
    .card {
        max-width: 650px;
    }
    
    .card-body {
        padding: 1.75rem;
    }
}

@media (min-width: 1200px) {
    .activity-form-content {
        padding-left: 3rem;
        padding-right: 3rem;
        padding-top: 2.5rem;
    }
    
    .card {
        max-width: 700px;
    }
}

@media (max-width: 768px) {
    .mobile-header-form,
    .mobile-menu-form {
        display: block;
    }
    
    .container {
        padding: 0;
    }
    
    .card {
        border-radius: 0;
        margin: 0;
    }
    
    .card-header {
        display: none;
    }
    
    body {
        padding-bottom: 70px;
    }
}
</style>

<!-- Mobile Header -->
<div class="mobile-header-form">
    <div class="d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-plus-circle"></i> Tambah Aktivitas</h4>
        <a href="<?php echo base_url('activities/index.php'); ?>" style="color: white; text-decoration: none;">
            <i class="bi bi-x-lg"></i>
        </a>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Desktop Sidebar (Modern) -->
        <aside class="sidebar-modern d-none d-md-block">
            <div class="sidebar-header">
                <i class="bi bi-cloud-sun fs-3"></i>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo base_url('dashboard.php'); ?>" class="nav-item" title="Dashboard">
                    <i class="bi bi-house-door-fill"></i>
                </a>
                <a href="<?php echo base_url('activities/index.php'); ?>" class="nav-item active" title="Aktivitas">
                    <i class="bi bi-calendar-event"></i>
                </a>
                <a href="<?php echo base_url('weather/index.php'); ?>" class="nav-item" title="Cuaca">
                    <i class="bi bi-cloud-lightning"></i>
                </a>
                <a href="<?php echo base_url('analytics.php'); ?>" class="nav-item" title="Analitik">
                    <i class="bi bi-graph-up"></i>
                </a>
                <a href="<?php echo base_url('profile.php'); ?>" class="nav-item" title="Profile">
                    <i class="bi bi-person"></i>
                </a>
                <?php if (isAdmin()): ?>
                <a href="<?php echo base_url('admin/index.php'); ?>" class="nav-item" title="Admin">
                    <i class="bi bi-gear"></i>
                </a>
                <?php endif; ?>
                <a href="<?php echo base_url('auth/logout.php'); ?>" class="nav-item" title="Logout">
                    <i class="bi bi-power"></i>
                </a>
            </nav>
        </aside>

        <div class="col-md-10 activity-form-content">
            <div class="card">
                <div class="card-header d-none d-md-block">
                    <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Tambah Aktivitas</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Aktivitas *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Kategori *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="olahraga">Olahraga</option>
                                    <option value="pendidikan">Pendidikan</option>
                                    <option value="kerja">Kerja</option>
                                    <option value="istirahat">Istirahat</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="activity_date" class="form-label">Tanggal *</label>
                                <input type="date" class="form-control" id="activity_date" name="activity_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Waktu Mulai</label>
                                <input type="time" class="form-control" id="start_time" name="start_time">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">Waktu Selesai</label>
                                <input type="time" class="form-control" id="end_time" name="end_time">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Lokasi</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="Contoh: Kampus, Taman, dll">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Simpan
                            </button>
                            <a href="<?php echo base_url('activities/index.php'); ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Menu -->
<div class="mobile-menu-form">
    <div class="mobile-menu-items">
        <a href="<?php echo base_url('dashboard.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-house-door-fill"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo base_url('activities/index.php'); ?>" class="mobile-menu-item active">
            <i class="bi bi-calendar-event-fill"></i>
            <span>Aktivitas</span>
        </a>
        <a href="<?php echo base_url('weather/index.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-cloud-sun-fill"></i>
            <span>Cuaca</span>
        </a>
        <a href="<?php echo base_url('analytics.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Analitik</span>
        </a>
        <a href="<?php echo base_url('profile.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-person-fill"></i>
            <span>Profile</span>
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

