<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Activity.php';

requireLogin();

$page_title = 'Aktivitas Harian';
$user_id = $_SESSION['user_id'];

$activity = new Activity($db);

// Apply filters
$date_filter = $_GET['date'] ?? null;
$category_filter = $_GET['category'] ?? null;

$activities = $activity->read($user_id, $date_filter);

// Filter by category if specified
if ($category_filter) {
    $activities = array_filter($activities, function($act) use ($category_filter) {
        return $act['category'] === $category_filter;
    });
    $activities = array_values($activities); // Re-index array
}

include '../includes/header.php';
?>

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
    flex-wrap: wrap;
    gap: 0.25rem;
    padding: 0 0.5rem;
}

.mobile-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    text-decoration: none;
    color: var(--text-muted);
    font-size: 0.65rem;
    transition: all 0.3s;
    padding: 0.5rem 0.25rem;
    border-radius: 8px;
    min-width: 0;
    flex: 1;
    max-width: 16.666%;
}

.mobile-menu-item i {
    font-size: 1.1rem;
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

/* Activities Mobile Cards */
.activities-mobile {
    display: none;
}

.activity-card-mobile {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-left: 4px solid var(--primary-color);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.activity-card-mobile:hover {
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
    border-left-width: 5px;
}

.activity-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.activity-date-time {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.activity-actions-mobile {
    display: flex;
    gap: 0.5rem;
}

.activity-actions-mobile a {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
}

.activity-title-mobile {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.activity-desc-mobile {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
}

.activity-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

.activity-badge-mobile {
    padding: 0.375rem 0.875rem;
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.activity-badge-mobile:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.activity-location-mobile {
    font-size: 0.85rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Filter Mobile */
.filter-mobile {
    display: none;
    background: var(--card-bg);
    padding: 1.25rem;
    margin-bottom: 1rem;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
    position: relative;
    z-index: 1;
}

.filter-mobile form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.filter-row {
    display: flex;
    gap: 0.75rem;
}

.filter-row .form-control,
.filter-row .form-select {
    flex: 1;
}

/* FAB Button */
.fab {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    color: white;
    border: none;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    z-index: 1000;
    transition: all 0.3s ease;
    text-decoration: none;
}

.fab:hover {
    transform: scale(1.15) translateY(-2px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.5);
}

.fab:active {
    transform: scale(1.05);
}

/* Sticky Add Button for Desktop Laptop - DISABLED untuk laptop */
.btn-add-sticky {
    display: none !important;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar-modern {
        display: none !important;
    }
    
    .activities-main-content {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding-top: 120px !important; /* Navbar 56px + Mobile header ~64px */
    }
    
    .mobile-header {
        display: block;
    }
    
    .mobile-menu {
        display: block;
    }
    
    .activities-mobile {
        display: block;
    }
    
    .table-responsive {
        display: none;
    }
    
    .fab {
        display: flex;
    }
    
    .filter-mobile {
        display: block;
    }
    
    .card .card-body form.row {
        display: none;
    }
    
    .container-fluid {
        padding: 0;
        margin-top: 0 !important;
    }
    
    .p-4 {
        padding: 1rem !important;
        padding-top: 0 !important;
    }
    
    /* Pastikan filter mobile tidak tertutup dan bisa di-scroll */
    .filter-mobile {
        margin-top: 0 !important;
        position: relative;
        z-index: 1;
        margin-bottom: 1rem;
    }
    
    /* Activities mobile container */
    .activities-mobile {
        padding-top: 0 !important;
        margin-top: 0 !important;
        padding-bottom: 100px; /* Space untuk FAB button */
    }
    
    body {
        padding-bottom: 70px;
        overflow-x: hidden;
    }
    
    /* Pastikan konten bisa di-scroll */
    .container-fluid {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-primary {
        width: 100%;
    }
}

@media (min-width: 769px) {
    .mobile-header,
    .mobile-menu,
    .activities-mobile,
    .filter-mobile,
    .fab {
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
    
    .activities-main-content {
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
        padding-top: 3rem !important;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .d-flex.justify-content-between.align-items-center {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
        position: relative;
        margin-top: 0;
        padding-top: 0;
    }
    
    .d-flex.justify-content-between.align-items-center .btn {
        position: relative;
        z-index: 10;
    }
    
    .activities-header {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    .card {
        border-radius: 20px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.06);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    
    /* Desktop Table Improvements */
    .table {
        font-size: 0.95rem;
        width: 100%;
        table-layout: auto;
    }
    
    .table th {
        background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        color: white;
        border: none;
        padding: 1rem 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    
    .table td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        word-wrap: break-word;
    }
    
    .table td:first-child {
        min-width: 130px;
        width: 130px;
    }
    
    .table td:nth-child(2) {
        min-width: 140px;
        width: 140px;
    }
    
    .table td:nth-child(3) {
        min-width: 250px;
        max-width: 400px;
    }
    
    .table td:nth-child(4) {
        min-width: 130px;
        width: 130px;
    }
    
    .table td:nth-child(5) {
        min-width: 150px;
        max-width: 200px;
    }
    
    .table td:last-child {
        min-width: 110px;
        width: 110px;
        text-align: center;
    }
    
    .table td small {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.85rem;
        color: var(--text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .table-hover tbody tr {
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    
    .table-hover tbody tr {
        transition: all 0.3s ease;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.1);
        border-left-color: var(--primary-color);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    
    .table-hover tbody tr td {
        transition: all 0.3s ease;
    }
    
    .card-header.bg-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%) !important;
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
    
    .btn-group .btn {
        border-radius: 8px;
        margin: 0 2px;
        padding: 0.5rem 0.875rem;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        border-color: transparent;
    }
    
    .btn-outline-danger:hover {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border-color: transparent;
        color: white;
    }
    
    .badge {
        padding: 0.5rem 0.875rem;
        font-size: 0.85rem;
        white-space: nowrap;
        font-weight: 600;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.05);
        border-radius: 4px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 4px;
    }
}

@media (min-width: 992px) {
    .activities-main-content {
        padding-left: 2.5rem;
        padding-right: 2.5rem;
    }
    
    .table td {
        padding: 1.25rem 1rem;
    }
    
    .table th {
        padding: 1.25rem 1rem;
    }
}

@media (min-width: 1200px) {
    .container-fluid {
        max-width: 1600px;
        margin: 0 auto;
    }
    
    .activities-main-content {
        padding-left: 3rem;
        padding-right: 3rem;
    }
}

@media (max-width: 991px) and (min-width: 769px) {
    .activities-main-content {
        margin-left: 80px;
        width: calc(100% - 80px);
        max-width: calc(100% - 80px);
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
    
    .table td:nth-child(3) {
        max-width: 250px;
    }
    
    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between.align-items-center h2 {
        font-size: 1.5rem !important;
        width: 100%;
    }
    
    .d-flex.justify-content-between.align-items-center .btn {
        width: 100%;
        max-width: 100%;
    }
}

/* Laptop Screen Fixes (1024px - 1366px) */
@media (min-width: 1024px) and (max-width: 1366px) {
    .activities-main-content {
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
        padding-top: 0 !important;
        margin-top: 56px !important;
    }
    
    .p-4 {
        padding-top: 3rem !important;
    }
    
    .d-flex.justify-content-between.align-items-center {
        flex-direction: row !important;
        flex-wrap: nowrap;
        gap: 1rem;
        align-items: center;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    .d-flex.justify-content-between.align-items-center h2 {
        font-size: 1.5rem !important;
        flex: 1;
        min-width: 0;
        margin: 0;
        max-width: calc(100% - 200px);
    }
    
    .d-flex.justify-content-between.align-items-center .btn {
        padding: 0.625rem 1.25rem !important;
        font-size: 0.95rem;
        white-space: nowrap;
        flex-shrink: 0;
        display: inline-flex !important;
        align-items: center;
        gap: 0.5rem;
        max-width: 100%;
        z-index: 10 !important;
        position: relative !important;
    }
    
    .btn-add-sticky {
        display: none !important;
    }
    
    .activities-header {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
}

/* Ensure no duplicate buttons */
@media (min-width: 769px) {
    .btn-add-sticky {
        display: none !important;
    }
    
    .activities-header {
        margin-top: 0;
        padding-top: 0;
    }
    
    .btn-tambah-aktivitas {
        position: relative !important;
        z-index: 10 !important;
    }
}

/* Prevent navbar overlap - already handled in media query above */
</style>

<!-- Mobile Header -->
<div class="mobile-header">
    <div class="mobile-header-content">
        <h1><i class="bi bi-calendar-event"></i> Aktivitas</h1>
        <div class="mobile-header-actions">
            <a href="<?php echo base_url('activities/create.php'); ?>" class="mobile-header-btn">
                <i class="bi bi-plus-lg"></i>
            </a>
            <button class="mobile-header-btn" onclick="toggleFilter()">
                <i class="bi bi-funnel"></i>
            </button>
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

        <div class="col-md-10 activities-main-content">
            <div class="p-4" style="padding-top: 3rem !important;">
                <!-- Desktop Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 d-none d-md-flex activities-header" style="flex-wrap: wrap; gap: 1rem; width: 100%; max-width: 100%; box-sizing: border-box; position: relative; z-index: 1; margin-top: 0;">
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 0.75rem; margin: 0; flex: 1; min-width: 0;">
                        <i class="bi bi-calendar-event" style="color: var(--primary-color);"></i> Aktivitas Harian
                    </h2>
                    <a href="<?php echo base_url('activities/create.php'); ?>" class="btn btn-primary btn-tambah-aktivitas" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); border: none; padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.3s ease; white-space: nowrap; flex-shrink: 0; position: relative; z-index: 10;">
                        <i class="bi bi-plus-circle"></i> Tambah Aktivitas
                    </a>
                </div>

                <!-- Mobile Filter -->
                <div class="filter-mobile" id="filterMobile" style="display: none;">
                    <form method="GET">
                        <div class="filter-row">
                            <input type="date" class="form-control" name="date" value="<?php echo $date_filter ?? date('Y-m-d'); ?>" placeholder="Tanggal">
                            <select class="form-select" name="category">
                                <option value="">Semua Kategori</option>
                                <option value="olahraga" <?php echo $category_filter === 'olahraga' ? 'selected' : ''; ?>>Olahraga</option>
                                <option value="pendidikan" <?php echo $category_filter === 'pendidikan' ? 'selected' : ''; ?>>Pendidikan</option>
                                <option value="kerja" <?php echo $category_filter === 'kerja' ? 'selected' : ''; ?>>Kerja</option>
                                <option value="istirahat" <?php echo $category_filter === 'istirahat' ? 'selected' : ''; ?>>Istirahat</option>
                                <option value="lainnya" <?php echo $category_filter === 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Terapkan Filter
                        </button>
                        <?php if ($date_filter || $category_filter): ?>
                        <a href="<?php echo base_url('activities/index.php'); ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Desktop Filter -->
                <div class="card card-modern mb-4 d-none d-md-block">
                    <div class="card-header-modern">
                        <h3><i class="bi bi-funnel"></i> Filter Aktivitas</h3>
                    </div>
                    <div class="card-body-modern">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tanggal</label>
                                <input type="date" class="form-control" name="date" value="<?php echo $date_filter ?? date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="category">
                                    <option value="">Semua</option>
                                    <option value="olahraga" <?php echo $category_filter === 'olahraga' ? 'selected' : ''; ?>>Olahraga</option>
                                    <option value="pendidikan" <?php echo $category_filter === 'pendidikan' ? 'selected' : ''; ?>>Pendidikan</option>
                                    <option value="kerja" <?php echo $category_filter === 'kerja' ? 'selected' : ''; ?>>Kerja</option>
                                    <option value="istirahat" <?php echo $category_filter === 'istirahat' ? 'selected' : ''; ?>>Istirahat</option>
                                    <option value="lainnya" <?php echo $category_filter === 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Activities List - Mobile -->
                <div class="activities-mobile">
                    <?php if (empty($activities)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x fs-1 text-muted"></i>
                            <p class="text-muted mt-3">Tidak ada aktivitas</p>
                            <a href="<?php echo base_url('activities/create.php'); ?>" class="btn btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Tambah Aktivitas Pertama
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                        <div class="activity-card-mobile">
                            <div class="activity-card-header">
                                <div class="activity-date-time">
                                    <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y', strtotime($act['activity_date'])); ?><br>
                                    <i class="bi bi-clock"></i> <?php echo $act['start_time']; ?> - <?php echo $act['end_time']; ?>
                                </div>
                                <div class="activity-actions-mobile">
                                    <a href="<?php echo base_url('activities/edit.php?id=' . $act['id']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?php echo base_url('activities/delete.php?id=' . $act['id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus aktivitas ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="activity-title-mobile">
                                <?php echo htmlspecialchars($act['title']); ?>
                            </div>
                            <?php if ($act['description']): ?>
                            <div class="activity-desc-mobile">
                                <?php echo htmlspecialchars($act['description']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="activity-meta">
                                <span class="activity-badge-mobile"><?php echo htmlspecialchars($act['category']); ?></span>
                                <?php if ($act['location']): ?>
                                <span class="activity-location-mobile">
                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($act['location']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Activities List - Desktop (Improved) -->
                <div class="card card-modern d-none d-md-block">
                    <div class="card-header-modern">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="bi bi-calendar-event"></i> Daftar Aktivitas</h3>
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; padding: 0.5rem 0.75rem; border-radius: 20px; font-size: 0.85rem;"><?php echo count($activities); ?> aktivitas</span>
                        </div>
                    </div>
                    <div class="card-body-modern">
                        <?php if (empty($activities)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                <p class="text-muted mt-3">Tidak ada aktivitas</p>
                                <a href="<?php echo base_url('activities/create.php'); ?>" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> Tambah Aktivitas
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 120px;">Tanggal</th>
                                            <th style="width: 150px;">Waktu</th>
                                            <th>Aktivitas</th>
                                            <th style="width: 120px;">Kategori</th>
                                            <th style="width: 150px;">Lokasi</th>
                                            <th style="width: 100px;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $act): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo date('d M Y', strtotime($act['activity_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('D', strtotime($act['activity_date'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($act['start_time'] && $act['end_time']): ?>
                                                    <i class="bi bi-clock text-primary"></i> <?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold mb-1"><?php echo htmlspecialchars($act['title']); ?></div>
                                                <?php if ($act['description']): ?>
                                                    <small class="text-muted d-block" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?php echo htmlspecialchars($act['description']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); color: white; padding: 0.5rem 0.875rem; border-radius: 20px; font-weight: 600; font-size: 0.85rem; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);">
                                                    <i class="bi bi-tag"></i> <?php echo htmlspecialchars($act['category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($act['location']): ?>
                                                    <i class="bi bi-geo-alt text-muted"></i> <?php echo htmlspecialchars($act['location']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="<?php echo base_url('activities/edit.php?id=' . $act['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="<?php echo base_url('activities/delete.php?id=' . $act['id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus aktivitas ini?')" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAB Button (Mobile) -->
<a href="<?php echo base_url('activities/create.php'); ?>" class="fab">
    <i class="bi bi-plus-lg"></i>
</a>

<!-- Mobile Menu -->
<div class="mobile-menu">
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

<script>
function toggleFilter() {
    const filter = document.getElementById('filterMobile');
    if (filter.style.display === 'none') {
        filter.style.display = 'block';
    } else {
        filter.style.display = 'none';
    }
}

// Add padding bottom for mobile menu
if (window.innerWidth <= 768) {
    document.body.style.paddingBottom = '70px';
}
</script>

<?php include '../includes/footer.php'; ?>
