<?php
// Set active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$active_menu = [
    'index.php' => 'dashboard',
    'weather.php' => 'weather',
    'activities.php' => 'activities',
    'users.php' => 'users',
    'notifications.php' => 'notifications',
    'notification-form.php' => 'notifications',
    'analytics.php' => 'analytics'
];

$active_item = $active_menu[$current_page] ?? '';
?>

<!-- Mobile Sidebar Overlay -->
<div class="mobile-admin-sidebar-overlay" id="mobileAdminSidebarOverlay" onclick="toggleMobileAdminSidebar()"></div>

<!-- Mobile Sidebar -->
<aside class="mobile-admin-sidebar" id="mobileAdminSidebar">
    <div class="mobile-admin-sidebar-header">
        <h3>
            <i class="bi bi-gear"></i>
            Menu Admin
        </h3>
    </div>
    <nav class="mobile-admin-sidebar-nav">
        <a href="<?php echo base_url('admin/index.php'); ?>" class="mobile-admin-sidebar-item <?php echo $active_item === 'dashboard' ? 'active' : ''; ?>" onclick="toggleMobileAdminSidebar();">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard Admin</span>
        </a>
        <a href="<?php echo base_url('admin/weather.php'); ?>" class="mobile-admin-sidebar-item <?php echo $active_item === 'weather' ? 'active' : ''; ?>" onclick="toggleMobileAdminSidebar();">
            <i class="bi bi-cloud-sun"></i>
            <span>Kelola Data Cuaca & Lokasi</span>
        </a>
        <a href="<?php echo base_url('admin/activities.php'); ?>" class="mobile-admin-sidebar-item <?php echo $active_item === 'activities' ? 'active' : ''; ?>" onclick="toggleMobileAdminSidebar();">
            <i class="bi bi-calendar-event"></i>
            <span>Kelola Aktivitas Harian</span>
        </a>
        <a href="<?php echo base_url('admin/users.php'); ?>" class="mobile-admin-sidebar-item <?php echo $active_item === 'users' ? 'active' : ''; ?>" onclick="toggleMobileAdminSidebar();">
            <i class="bi bi-people"></i>
            <span>Kelola User</span>
        </a>
        <a href="<?php echo base_url('admin/notifications.php'); ?>" class="mobile-admin-sidebar-item <?php echo $active_item === 'notifications' ? 'active' : ''; ?>" onclick="toggleMobileAdminSidebar();">
            <i class="bi bi-bell"></i>
            <span>Kelola Notifikasi Cuaca</span>
        </a>
        <a href="<?php echo base_url('analytics.php'); ?>" class="mobile-admin-sidebar-item <?php echo $active_item === 'analytics' ? 'active' : ''; ?>" onclick="toggleMobileAdminSidebar();">
            <i class="bi bi-graph-up"></i>
            <span>Lihat Analitik & Grafik</span>
        </a>
    </nav>
</aside>

<!-- Desktop Sidebar -->
<aside class="admin-sidebar-desktop d-none d-md-block">
    <div class="admin-sidebar-desktop-header">
        <h3>
            <i class="bi bi-gear"></i>
            Menu Admin
        </h3>
    </div>
    <nav class="admin-sidebar-desktop-nav">
        <a href="<?php echo base_url('admin/index.php'); ?>" class="admin-sidebar-desktop-item <?php echo $active_item === 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard Admin</span>
        </a>
        <a href="<?php echo base_url('admin/weather.php'); ?>" class="admin-sidebar-desktop-item <?php echo $active_item === 'weather' ? 'active' : ''; ?>">
            <i class="bi bi-cloud-sun"></i>
            <span>Kelola Data Cuaca & Lokasi</span>
        </a>
        <a href="<?php echo base_url('admin/activities.php'); ?>" class="admin-sidebar-desktop-item <?php echo $active_item === 'activities' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-event"></i>
            <span>Kelola Aktivitas Harian</span>
        </a>
        <a href="<?php echo base_url('admin/users.php'); ?>" class="admin-sidebar-desktop-item <?php echo $active_item === 'users' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            <span>Kelola User</span>
        </a>
        <a href="<?php echo base_url('admin/notifications.php'); ?>" class="admin-sidebar-desktop-item <?php echo $active_item === 'notifications' ? 'active' : ''; ?>">
            <i class="bi bi-bell"></i>
            <span>Kelola Notifikasi Cuaca</span>
        </a>
        <a href="<?php echo base_url('analytics.php'); ?>" class="admin-sidebar-desktop-item <?php echo $active_item === 'analytics' ? 'active' : ''; ?>">
            <i class="bi bi-graph-up"></i>
            <span>Lihat Analitik & Grafik</span>
        </a>
    </nav>
</aside>

