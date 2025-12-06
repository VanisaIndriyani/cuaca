<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Activity.php';
require_once __DIR__ . '/../app/Models/WeatherData.php';
require_once __DIR__ . '/../app/Models/Notification.php';

requireAdmin();

$page_title = 'Admin Panel';

$userModel = new User($db);
$activityModel = new Activity($db);
$weatherModel = new WeatherData($db);
$notificationModel = new Notification($db);

// Get stats
$total_users = count($userModel->getAll());
$total_activities = count($activityModel->read());
$total_weather = count($weatherModel->getAll(1000));
$total_notifications = count($notificationModel->getAll(1000));

include '../includes/header.php';
?>

<style>

.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.admin-stat-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    border-radius: 16px;
    padding: 2rem;
    color: white;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
}

.admin-stat-card i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.95;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.admin-stat-card h3 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0.75rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.admin-stat-card p {
    font-size: 1rem;
    opacity: 0.95;
    margin: 0;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .mobile-admin-header {
        display: block;
    }
    
    .mobile-admin-sidebar {
        display: block;
    }
    
    .admin-sidebar-desktop {
        display: none;
    }
    
    .admin-main-content {
        margin-left: 0;
        padding: 1rem;
    }
    
    .admin-content-card {
        padding: 1.5rem;
        border-radius: 12px;
    }
    
    .admin-stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .admin-stat-card {
        padding: 1.25rem;
    }
    
    body {
        padding-bottom: 2rem;
    }
    
    .container-fluid {
        padding-bottom: 2rem;
    }
}

@media (min-width: 769px) {
    .mobile-admin-header,
    .mobile-admin-sidebar,
    .mobile-admin-sidebar-overlay {
        display: none !important;
    }
    
    .admin-main-content {
        margin-left: 280px;
        margin-top: 56px;
        width: calc(100% - 280px);
        padding-top: 2.5rem;
    }
    
    .admin-sidebar-desktop {
        top: 56px;
        height: calc(100vh - 56px);
    }
}

/* Laptop specific styles (1024px - 1399px) */
@media (min-width: 1024px) and (max-width: 1399px) {
    .admin-main-content {
        padding-left: 2rem;
        padding-right: 2rem;
        padding-top: 2.5rem;
    }
    
    .admin-content-card {
        padding: 2rem;
    }
    
    .admin-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
}

@media (min-width: 1200px) {
    .admin-main-content {
        padding-left: 3rem;
        padding-right: 3rem;
        padding-top: 2.5rem;
    }
    
    .admin-stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>

<link rel="stylesheet" href="<?php echo base_url('admin/includes/admin-layout.css'); ?>">

<?php include 'includes/admin-header.php'; ?>
<?php include 'includes/admin-sidebar.php'; ?>

<div class="container-fluid">
    <div class="row">

        <!-- Main Content -->
        <main class="admin-main-content">
            <div class="admin-content-card">
                <h2>
                    <i class="bi bi-speedometer2"></i>
                    Dashboard Admin
                </h2>
                
                <!-- Stats Cards -->
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <i class="bi bi-people"></i>
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="admin-stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="bi bi-calendar-event"></i>
                        <h3><?php echo $total_activities; ?></h3>
                        <p>Total Aktivitas</p>
                    </div>
                    <div class="admin-stat-card" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                        <i class="bi bi-cloud-sun"></i>
                        <h3><?php echo $total_weather; ?></h3>
                        <p>Data Cuaca</p>
                    </div>
                    <div class="admin-stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="bi bi-bell"></i>
                        <h3><?php echo $total_notifications; ?></h3>
                        <p>Notifikasi</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <h3 class="mb-4" style="color: var(--text-color); font-size: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-lightning-charge" style="color: var(--primary-color);"></i>
                            Aksi Cepat
                        </h3>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="<?php echo base_url('admin/users.php'); ?>" class="btn btn-primary" style="padding: 0.875rem 1.5rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.3s ease;">
                                <i class="bi bi-people"></i> Kelola User
                            </a>
                            <a href="<?php echo base_url('admin/activities.php'); ?>" class="btn btn-success" style="padding: 0.875rem 1.5rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: all 0.3s ease;">
                                <i class="bi bi-calendar-event"></i> Kelola Aktivitas
                            </a>
                            <a href="<?php echo base_url('admin/notifications.php'); ?>" class="btn btn-warning" style="padding: 0.875rem 1.5rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); transition: all 0.3s ease;">
                                <i class="bi bi-bell"></i> Kelola Notifikasi
                            </a>
                            <a href="<?php echo base_url('analytics.php'); ?>" class="btn btn-info" style="padding: 0.875rem 1.5rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3); transition: all 0.3s ease;">
                                <i class="bi bi-graph-up"></i> Lihat Analitik
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>

<?php include '../includes/footer.php'; ?>
