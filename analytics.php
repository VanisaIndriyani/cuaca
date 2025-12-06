<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Services/AnalyticsService.php';
require_once __DIR__ . '/app/Models/Activity.php';
require_once __DIR__ . '/app/Models/WeatherData.php';

requireLogin();

$page_title = 'Analitik';
$page_icon = 'graph-up';
$is_admin = isAdmin();
$user_id = $_SESSION['user_id'];
$location = $_SESSION['user_location'] ?? 'Jakarta';

$analytics = new AnalyticsService($db);
$activityModel = new Activity($db);
$weatherModel = new WeatherData($db);

// Date range for report
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Handle CSV export - must be before any output
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Ensure no output before headers
    if (ob_get_level()) {
        ob_clean();
    }
    
    $csv = $analytics->generateCSVReport($user_id, $start_date, $end_date);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan_aktivitas_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    echo $csv;
    exit;
}

// Get analytics data
$avg_temp = $analytics->getWeeklyAverageTemperature($location);
$avg_humidity = $analytics->getWeeklyAverageHumidity($location);
$activity_stats = $analytics->getActivityStatsByCategory($user_id);
$temp_trend = $analytics->getTemperatureTrend($location, 7);
$humidity_trend = $analytics->getHumidityTrend($location, 7);

include 'includes/header.php';
?>

<?php if ($is_admin): ?>
<link rel="stylesheet" href="<?php echo base_url('admin/includes/admin-layout.css'); ?>">
<?php endif; ?>

<style>
/* Mobile Menu */
.mobile-menu {
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

.mobile-menu-item.active,
.mobile-menu-item:hover {
    color: var(--primary-color);
    background: rgba(59, 130, 246, 0.1);
}

/* Mobile Header */
.mobile-header {
    display: none;
    background: var(--primary-color);
    color: white;
    padding: 1rem;
    position: fixed;
    top: 56px;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 999;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mobile-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-header h1 {
    font-size: 1.25rem;
    margin: 0;
    font-weight: 600;
}

.mobile-header-actions {
    display: flex;
    gap: 0.5rem;
}

.mobile-header-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s;
}

.mobile-header-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Metrics Cards Mobile */
.metric-card-mobile {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.5rem 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    text-align: center;
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.metric-card-mobile::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.metric-card-mobile:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-4px);
}

.metric-card-mobile:hover::before {
    opacity: 1;
}

.metric-icon-mobile {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.1));
    transition: all 0.3s ease;
}

.metric-card-mobile:hover .metric-icon-mobile {
    transform: scale(1.1);
}

.metric-value-mobile {
    font-size: 2rem;
    font-weight: 700;
    margin: 0.5rem 0;
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.2;
}

.metric-label-mobile {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin: 0;
}

/* Chart Cards Mobile */
.chart-card-mobile {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.chart-card-mobile:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.chart-card-mobile h5 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid rgba(59, 130, 246, 0.1);
}

.chart-card-mobile h5 i {
    color: var(--primary-color);
    font-size: 1.25rem;
}

.chart-container-mobile {
    min-height: 220px;
    position: relative;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar-modern {
        display: none !important;
    }
    
    .mobile-header {
        display: block;
    }
    
    .mobile-menu {
        display: block;
    }
    
    .analytics-main-content {
        width: 100% !important;
        padding: 0 !important;
        margin-left: 0 !important;
        padding-top: 120px !important; /* Navbar 56px + Mobile header ~64px */
    }
    
    .container-fluid {
        padding: 0;
        margin-top: 0 !important;
        overflow-x: hidden;
    }
    
    .p-4 {
        padding: 1rem !important;
        padding-top: 0 !important;
        padding-bottom: 80px !important; /* Space untuk bottom nav */
    }
    
    .d-flex.justify-content-between {
        display: none !important;
    }
    
    .row.mb-4 .col-md-3 {
        width: 50%;
        max-width: 50%;
        flex: 0 0 50%;
    }
    
    .row .col-md-6 {
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
    }
    
    body {
        padding-bottom: 70px;
        overflow-x: hidden;
    }
    
    .card {
        margin-bottom: 1rem;
    }
    
    .metric-card-mobile {
        margin-bottom: 1rem;
    }
    
    .chart-card-mobile {
        margin-bottom: 1.5rem;
    }
    
    /* Pastikan konten bisa di-scroll */
    .analytics-main-content {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Pastikan metric cards tidak tertutup */
    .metric-card-mobile {
        position: relative;
        z-index: 1;
    }
    
    /* Pastikan chart cards tidak tertutup */
    .chart-card-mobile {
        position: relative;
        z-index: 1;
    }
}

@media (min-width: 769px) {
    .mobile-header,
    .mobile-menu {
        display: none !important;
    }
    
    /* Desktop Layout */
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
        padding: 0;
    }
    
    .row {
        margin: 0;
        display: flex;
    }
    
    .analytics-main-content {
        flex: 1;
        margin-left: 80px;
        margin-top: 56px;
        width: calc(100% - 80px);
        max-width: calc(100% - 80px);
        padding-left: 2rem;
        padding-right: 2rem;
        padding-top: 0;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .p-4 {
        padding: 2rem !important;
        padding-top: 2.5rem !important;
    }
    
    .d-flex.justify-content-between.align-items-center {
        margin-top: 0;
        padding-top: 0;
    }
    
    .card-modern {
        border-radius: 20px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .card-modern:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .card-header-modern {
        background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        color: white;
        padding: 1.5rem 1.75rem;
        border-bottom: none;
        position: relative;
        overflow: hidden;
    }
    
    .card-header-modern::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    
    .card-header-modern h3 {
        position: relative;
        z-index: 1;
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
    }
    
    .card-body-modern {
        padding: 1.75rem;
        background: var(--card-bg);
    }
}

@media (min-width: 992px) {
    .analytics-main-content {
        padding-left: 2.5rem;
        padding-right: 2.5rem;
    }
}

@media (min-width: 1200px) {
    .container-fluid {
        max-width: 1600px;
        margin: 0 auto;
    }
    
    .analytics-main-content {
        padding-left: 3rem;
        padding-right: 3rem;
    }
}
</style>

<?php if ($is_admin): ?>
    <!-- Admin Layout -->
    <link rel="stylesheet" href="<?php echo base_url('admin/includes/admin-layout.css'); ?>">
    <?php include 'admin/includes/admin-header.php'; ?>
    <?php include 'admin/includes/admin-sidebar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <main class="admin-main-content">
<?php else: ?>
    <!-- User Layout -->
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="mobile-header-content">
            <h1><i class="bi bi-graph-up"></i> Analitik</h1>
            <div class="mobile-header-actions">
                <a href="<?php echo base_url('analytics.php?export=csv&start_date=' . $start_date . '&end_date=' . $end_date); ?>" class="mobile-header-btn" title="Export CSV">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="container-fluid">
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
                    <a href="<?php echo base_url('activities/index.php'); ?>" class="nav-item" title="Aktivitas">
                        <i class="bi bi-calendar-event"></i>
                    </a>
                    <a href="<?php echo base_url('weather/index.php'); ?>" class="nav-item" title="Cuaca">
                        <i class="bi bi-cloud-lightning"></i>
                    </a>
                    <a href="<?php echo base_url('analytics.php'); ?>" class="nav-item active" title="Analitik">
                        <i class="bi bi-graph-up"></i>
                    </a>
                    <a href="<?php echo base_url('profile.php'); ?>" class="nav-item" title="Profile">
                        <i class="bi bi-person"></i>
                    </a>
                    <a href="<?php echo base_url('auth/logout.php'); ?>" class="nav-item" title="Logout">
                        <i class="bi bi-power"></i>
                    </a>
                </nav>
            </aside>

            <div class="col-md-10 analytics-main-content">
<?php endif; ?>
                <div class="<?php echo $is_admin ? 'admin-content-card' : 'p-4'; ?>" style="padding-top: 2.5rem !important;">
                    <!-- Desktop Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 d-none d-md-flex" style="margin-top: 0; padding-top: 0;">
                        <h2 style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 0.75rem;">
                            <i class="bi bi-graph-up" style="color: var(--primary-color);"></i> Analitik & Laporan
                        </h2>
                        <a href="<?php echo base_url('analytics.php?export=csv&start_date=' . $start_date . '&end_date=' . $end_date); ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); border: none; padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.3s ease;">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                    </div>

                <!-- Metrics Cards -->
                <div class="row mb-4">
                    <!-- Mobile: 2 columns, Desktop: 4 columns -->
                    <div class="col-6 col-md-3 mb-3">
                        <div class="metric-card-mobile d-md-none">
                            <div class="metric-icon-mobile text-primary">
                                <i class="bi bi-thermometer-half"></i>
                            </div>
                            <div class="metric-value-mobile"><?php echo $avg_temp; ?>°C</div>
                            <p class="metric-label-mobile">Rata-rata Suhu Minggu Ini</p>
                        </div>
                        <div class="card card-modern text-center d-none d-md-block">
                            <div class="card-header-modern" style="padding: 1rem;">
                                <h3 style="font-size: 1rem; margin: 0;"><i class="bi bi-thermometer-half"></i></h3>
                            </div>
                            <div class="card-body-modern">
                                <h4 class="mt-2" style="font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo $avg_temp; ?>°C</h4>
                                <p class="text-muted mb-0" style="font-size: 0.9rem; margin-top: 0.5rem;">Rata-rata Suhu Minggu Ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="metric-card-mobile d-md-none">
                            <div class="metric-icon-mobile text-info">
                                <i class="bi bi-droplet"></i>
                            </div>
                            <div class="metric-value-mobile"><?php echo $avg_humidity; ?>%</div>
                            <p class="metric-label-mobile">Rata-rata Kelembapan</p>
                        </div>
                        <div class="card card-modern text-center d-none d-md-block">
                            <div class="card-header-modern" style="padding: 1rem; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                                <h3 style="font-size: 1rem; margin: 0;"><i class="bi bi-droplet"></i></h3>
                            </div>
                            <div class="card-body-modern">
                                <h4 class="mt-2" style="font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo $avg_humidity; ?>%</h4>
                                <p class="text-muted mb-0" style="font-size: 0.9rem; margin-top: 0.5rem;">Rata-rata Kelembapan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="metric-card-mobile d-md-none">
                            <div class="metric-icon-mobile text-success">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="metric-value-mobile"><?php echo count($activityModel->read($user_id)); ?></div>
                            <p class="metric-label-mobile">Total Aktivitas</p>
                        </div>
                        <div class="card card-modern text-center d-none d-md-block">
                            <div class="card-header-modern" style="padding: 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <h3 style="font-size: 1rem; margin: 0;"><i class="bi bi-calendar-event"></i></h3>
                            </div>
                            <div class="card-body-modern">
                                <h4 class="mt-2" style="font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, #10b981 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo count($activityModel->read($user_id)); ?></h4>
                                <p class="text-muted mb-0" style="font-size: 0.9rem; margin-top: 0.5rem;">Total Aktivitas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="metric-card-mobile d-md-none">
                            <div class="metric-icon-mobile text-warning">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div class="metric-value-mobile"><?php echo count($activity_stats); ?></div>
                            <p class="metric-label-mobile">Kategori Aktivitas</p>
                        </div>
                        <div class="card card-modern text-center d-none d-md-block">
                            <div class="card-header-modern" style="padding: 1rem; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                <h3 style="font-size: 1rem; margin: 0;"><i class="bi bi-graph-up-arrow"></i></h3>
                            </div>
                            <div class="card-body-modern">
                                <h4 class="mt-2" style="font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo count($activity_stats); ?></h4>
                                <p class="text-muted mb-0" style="font-size: 0.9rem; margin-top: 0.5rem;">Kategori Aktivitas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="chart-card-mobile d-md-none">
                            <h5><i class="bi bi-graph-up"></i> Tren Suhu (7 Hari)</h5>
                            <div class="chart-container-mobile">
                                <canvas id="temperatureChartMobile"></canvas>
                            </div>
                        </div>
                        <div class="card card-modern d-none d-md-block">
                            <div class="card-header-modern">
                                <h3><i class="bi bi-graph-up"></i> Tren Suhu (7 Hari)</h3>
                            </div>
                            <div class="card-body-modern">
                                <canvas id="temperatureChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="chart-card-mobile d-md-none">
                            <h5><i class="bi bi-droplet"></i> Tren Kelembapan (7 Hari)</h5>
                            <div class="chart-container-mobile">
                                <canvas id="humidityChartMobile"></canvas>
                            </div>
                        </div>
                        <div class="card card-modern d-none d-md-block">
                            <div class="card-header-modern" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <h3><i class="bi bi-droplet"></i> Tren Kelembapan (7 Hari)</h3>
                            </div>
                            <div class="card-body-modern">
                                <canvas id="humidityChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="chart-card-mobile d-md-none">
                            <h5><i class="bi bi-bar-chart"></i> Aktivitas per Kategori (Bar)</h5>
                            <div class="chart-container-mobile">
                                <canvas id="activityBarChartMobile"></canvas>
                            </div>
                        </div>
                        <div class="card card-modern d-none d-md-block">
                            <div class="card-header-modern" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                                <h3><i class="bi bi-bar-chart"></i> Aktivitas per Kategori (Bar)</h3>
                            </div>
                            <div class="card-body-modern">
                                <canvas id="activityBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="chart-card-mobile d-md-none">
                            <h5><i class="bi bi-pie-chart"></i> Aktivitas per Kategori (Pie)</h5>
                            <div class="chart-container-mobile">
                                <canvas id="activityPieChartMobile"></canvas>
                            </div>
                        </div>
                        <div class="card card-modern d-none d-md-block">
                            <div class="card-header-modern" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                                <h3><i class="bi bi-pie-chart"></i> Aktivitas per Kategori (Pie)</h3>
                            </div>
                            <div class="card-body-modern">
                                <canvas id="activityPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            <?php if ($is_admin): ?>
            </main>
            <?php else: ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$is_admin): ?>
<!-- Mobile Menu -->
<div class="mobile-menu">
    <div class="mobile-menu-items">
        <a href="<?php echo base_url('dashboard.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-house-door-fill"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo base_url('activities/index.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-calendar-event-fill"></i>
            <span>Aktivitas</span>
        </a>
        <a href="<?php echo base_url('weather/index.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-cloud-sun-fill"></i>
            <span>Cuaca</span>
        </a>
        <a href="<?php echo base_url('analytics.php'); ?>" class="mobile-menu-item active">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Analitik</span>
        </a>
        <a href="<?php echo base_url('profile.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-person-fill"></i>
            <span>Profile</span>
        </a>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tempData = <?php echo json_encode($temp_trend ?? []); ?>;
    const humData = <?php echo json_encode($humidity_trend ?? []); ?>;
    const activityData = <?php echo json_encode($activity_stats ?? []); ?>;
    
    // Temperature Chart - Mobile
    const tempCtxMobile = document.getElementById('temperatureChartMobile');
    if (tempCtxMobile && tempData.length > 0) {
        new Chart(tempCtxMobile, {
            type: 'line',
            data: {
                labels: tempData.map(d => new Date(d.recorded_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })),
                datasets: [{
                    label: 'Suhu (°C)',
                    data: tempData.map(d => parseFloat(d.temperature)),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }
    
    // Temperature Chart - Desktop
    const tempCtx = document.getElementById('temperatureChart');
    if (tempCtx && tempData.length > 0) {
        new Chart(tempCtx, {
            type: 'line',
            data: {
                labels: tempData.map(d => new Date(d.recorded_at).toLocaleDateString('id-ID')),
                datasets: [{
                    label: 'Suhu (°C)',
                    data: tempData.map(d => parseFloat(d.temperature)),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true
            }
        });
    }
    
    // Humidity Chart - Mobile
    const humCtxMobile = document.getElementById('humidityChartMobile');
    if (humCtxMobile && humData.length > 0) {
        new Chart(humCtxMobile, {
            type: 'line',
            data: {
                labels: humData.map(d => new Date(d.recorded_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })),
                datasets: [{
                    label: 'Kelembapan (%)',
                    data: humData.map(d => parseFloat(d.humidity)),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }
    
    // Humidity Chart - Desktop
    const humCtx = document.getElementById('humidityChart');
    if (humCtx && humData.length > 0) {
        new Chart(humCtx, {
            type: 'line',
            data: {
                labels: humData.map(d => new Date(d.recorded_at).toLocaleDateString('id-ID')),
                datasets: [{
                    label: 'Kelembapan (%)',
                    data: humData.map(d => parseFloat(d.humidity)),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true
            }
        });
    }
    
    // Activity Bar Chart - Mobile
    const activityBarCtxMobile = document.getElementById('activityBarChartMobile');
    if (activityBarCtxMobile && activityData.length > 0) {
        new Chart(activityBarCtxMobile, {
            type: 'bar',
            data: {
                labels: activityData.map(d => d.category),
                datasets: [{
                    label: 'Jumlah Aktivitas',
                    data: activityData.map(d => parseInt(d.count)),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Activity Bar Chart - Desktop
    const activityBarCtx = document.getElementById('activityBarChart');
    if (activityBarCtx && activityData.length > 0) {
        new Chart(activityBarCtx, {
            type: 'bar',
            data: {
                labels: activityData.map(d => d.category),
                datasets: [{
                    label: 'Jumlah Aktivitas',
                    data: activityData.map(d => parseInt(d.count)),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Activity Pie Chart - Mobile
    const activityPieCtxMobile = document.getElementById('activityPieChartMobile');
    if (activityPieCtxMobile && activityData.length > 0) {
        new Chart(activityPieCtxMobile, {
            type: 'pie',
            data: {
                labels: activityData.map(d => d.category),
                datasets: [{
                    data: activityData.map(d => parseInt(d.count)),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Activity Pie Chart - Desktop
    const activityPieCtx = document.getElementById('activityPieChart');
    if (activityPieCtx && activityData.length > 0) {
        new Chart(activityPieCtx, {
            type: 'pie',
            data: {
                labels: activityData.map(d => d.category),
                datasets: [{
                    data: activityData.map(d => parseInt(d.count)),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    }
});
</script>

<?php if ($is_admin): ?>
<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>
<?php endif; ?>

<?php
include 'includes/footer.php';
?>
