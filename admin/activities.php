<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Activity.php';

requireAdmin();

$page_title = 'Kelola Aktivitas Harian';
$page_icon = 'calendar-event';
$activityModel = new Activity($db);
$activities = $activityModel->read(); // Get all activities (admin)

include '../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo base_url('admin/includes/admin-layout.css'); ?>">

<style>
.admin-main-content {
    margin-top: 56px !important;
    padding-top: 2.5rem !important;
}

.admin-content-card {
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.admin-content-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.admin-content-card h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.table {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 0;
}

.table thead {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    color: white;
}

.table thead th {
    border: none;
    padding: 1.25rem 1rem;
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.table tbody tr:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: scale(1.01);
}

.table tbody tr:last-child {
    border-bottom: none;
}

.table tbody td {
    padding: 1.25rem 1rem;
    vertical-align: middle;
    color: #1f2937;
    font-weight: 500;
}

.badge {
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.85rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-danger {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.empty-state {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state i {
    font-size: 3rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.empty-state p {
    color: #4b5563;
    font-size: 1.1rem;
    margin: 0;
    font-weight: 500;
}

@media (min-width: 1024px) and (max-width: 1399px) {
    .admin-main-content {
        padding-left: 2rem !important;
        padding-right: 2rem !important;
    }
    
    .admin-content-card {
        padding: 2rem;
    }
}

@media (min-width: 1200px) {
    .admin-main-content {
        padding-left: 3rem !important;
        padding-right: 3rem !important;
    }
}
</style>

<?php include 'includes/admin-header.php'; ?>
<?php include 'includes/admin-sidebar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="admin-main-content">
            <div class="admin-content-card">
                <h2>
                    <i class="bi bi-calendar-event"></i>
                    Kelola Aktivitas Harian
                </h2>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>User</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Lokasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Tidak ada aktivitas</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                foreach ($activities as $act): 
                                ?>
                                <tr>
                                    <td><strong style="color: #111827;"><?php echo $no++; ?></strong></td>
                                    <td style="color: #1f2937; font-weight: 500;"><?php echo htmlspecialchars($act['user_name'] ?? 'N/A'); ?></td>
                                    <td><strong style="color: #111827;"><?php echo htmlspecialchars($act['title']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);">
                                            <?php echo htmlspecialchars(ucfirst($act['category'])); ?>
                                        </span>
                                    </td>
                                    <td style="color: #1f2937; font-weight: 500;"><?php echo date('d/m/Y', strtotime($act['activity_date'])); ?></td>
                                    <td style="color: #1f2937; font-weight: 500;">
                                        <?php if ($act['start_time'] && $act['end_time']): ?>
                                            <?php echo date('H:i', strtotime($act['start_time'])); ?> - <?php echo date('H:i', strtotime($act['end_time'])); ?>
                                        <?php else: ?>
                                            <span style="color: #6b7280;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #1f2937; font-weight: 500;">
                                        <?php if (!empty($act['location'])): ?>
                                            <i class="bi bi-geo-alt" style="color: #6b7280;"></i> <?php echo htmlspecialchars($act['location']); ?>
                                        <?php else: ?>
                                            <span style="color: #6b7280;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo base_url('admin/activity-delete.php?id=' . $act['id']); ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus aktivitas ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
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
