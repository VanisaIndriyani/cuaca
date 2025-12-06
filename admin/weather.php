<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/WeatherData.php';

requireAdmin();

$page_title = 'Kelola Data Cuaca & Lokasi';
$weatherModel = new WeatherData($db);
$error = '';
$success = '';
$edit_id = null;
$edit_data = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $weather = new WeatherData($db);
            $weather->location = $_POST['location'] ?? '';
            $weather->latitude = $_POST['latitude'] ?? null;
            $weather->longitude = $_POST['longitude'] ?? null;
            $weather->temperature = $_POST['temperature'] ?? 0;
            $weather->feels_like = $_POST['feels_like'] ?? null;
            $weather->humidity = $_POST['humidity'] ?? null;
            $weather->pressure = $_POST['pressure'] ?? null;
            $weather->wind_speed = $_POST['wind_speed'] ?? null;
            $weather->wind_direction = $_POST['wind_direction'] ?? null;
            $weather->condition = $_POST['condition'] ?? null;
            $weather->description = $_POST['description'] ?? null;
            $weather->icon = $_POST['icon'] ?? null;
            $weather->uv_index = $_POST['uv_index'] ?? null;
            $weather->visibility = $_POST['visibility'] ?? null;
            $weather->recorded_at = $_POST['recorded_at'] ?? date('Y-m-d H:i:s');
            
            if (empty($weather->location)) {
                $error = 'Lokasi harus diisi';
            } else {
                if ($weather->create()) {
                    $success = 'Data cuaca berhasil ditambahkan';
                    header('Location: ' . base_url('admin/weather.php?success=created'));
                    exit;
                } else {
                    $error = 'Gagal menambahkan data cuaca';
                }
            }
        } elseif ($action === 'update') {
            $weather = new WeatherData($db);
            $weather->id = $_POST['id'] ?? 0;
            $weather->location = $_POST['location'] ?? '';
            $weather->latitude = $_POST['latitude'] ?? null;
            $weather->longitude = $_POST['longitude'] ?? null;
            $weather->temperature = $_POST['temperature'] ?? 0;
            $weather->feels_like = $_POST['feels_like'] ?? null;
            $weather->humidity = $_POST['humidity'] ?? null;
            $weather->pressure = $_POST['pressure'] ?? null;
            $weather->wind_speed = $_POST['wind_speed'] ?? null;
            $weather->wind_direction = $_POST['wind_direction'] ?? null;
            $weather->condition = $_POST['condition'] ?? null;
            $weather->description = $_POST['description'] ?? null;
            $weather->icon = $_POST['icon'] ?? null;
            $weather->uv_index = $_POST['uv_index'] ?? null;
            $weather->visibility = $_POST['visibility'] ?? null;
            $weather->recorded_at = $_POST['recorded_at'] ?? date('Y-m-d H:i:s');
            
            if (empty($weather->location)) {
                $error = 'Lokasi harus diisi';
            } else {
                if ($weather->update()) {
                    $success = 'Data cuaca berhasil diupdate';
                    header('Location: ' . base_url('admin/weather.php?success=updated'));
                    exit;
                } else {
                    $error = 'Gagal mengupdate data cuaca';
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $weather = new WeatherData($db);
    $weather->id = $_GET['delete'];
    if ($weather->delete()) {
        header('Location: ' . base_url('admin/weather.php?success=deleted'));
        exit;
    } else {
        $error = 'Gagal menghapus data cuaca';
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $success = 'Data cuaca berhasil ditambahkan';
            break;
        case 'updated':
            $success = 'Data cuaca berhasil diupdate';
            break;
        case 'deleted':
            $success = 'Data cuaca berhasil dihapus';
            break;
    }
}

// Handle edit
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_data = $weatherModel->getById($edit_id);
    if (!$edit_data) {
        $error = 'Data tidak ditemukan';
        $edit_id = null;
    }
}

$weather_data = $weatherModel->getAll(100);

include '../includes/header.php';
?>

<style>

.admin-content-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(0, 0, 0, 0.06);
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.admin-content-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
}

.btn-action:hover {
    transform: translateY(-2px);
}

.btn-edit {
    background: #3b82f6;
    color: white;
}

.btn-edit:hover {
    background: #2563eb;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.table {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
}

.table thead {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    color: white;
}

.table thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
}

.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: scale(1.01);
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.modal-header {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    color: white;
    border-radius: 16px 16px 0 0;
    border: none;
    padding: 1.5rem;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

.modal-body {
    padding: 2rem;
}

.form-label {
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.5rem;
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

.admin-content-card h2 {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
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
    
    body {
        padding-bottom: 0;
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
}

@media (min-width: 1200px) {
    .admin-main-content {
        padding-left: 3rem;
        padding-right: 3rem;
        padding-top: 2.5rem;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <i class="bi bi-cloud-sun"></i>
                        Kelola Data Cuaca & Lokasi
                    </h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#weatherModal" onclick="resetForm()">
                        <i class="bi bi-plus-circle"></i> Tambah Data
                    </button>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lokasi</th>
                                <th>Suhu</th>
                                <th>Kondisi</th>
                                <th>Kelembaban</th>
                                <th>Tekanan</th>
                                <th>Angin</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($weather_data)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 2rem; color: var(--text-muted);"></i>
                                    <p class="mt-2 text-muted">Tidak ada data cuaca</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                foreach ($weather_data as $data): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($data['location']); ?></td>
                                    <td><?php echo htmlspecialchars($data['temperature']); ?>°C</td>
                                    <td><?php echo htmlspecialchars($data['condition'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($data['humidity'] ?? 'N/A'); ?>%</td>
                                    <td><?php echo htmlspecialchars($data['pressure'] ?? 'N/A'); ?> hPa</td>
                                    <td><?php echo htmlspecialchars($data['wind_speed'] ?? 'N/A'); ?> km/h</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($data['recorded_at'])); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn-action btn-edit" onclick="editWeather(<?php echo $data['id']; ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <a href="?delete=<?php echo $data['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
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

<!-- Modal for Create/Edit -->
<div class="modal fade" id="weatherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cloud-sun"></i>
                    <span id="modalTitle">Tambah Data Cuaca</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="weatherForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="formId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Lokasi *</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="temperature" class="form-label">Suhu (°C) *</label>
                            <input type="number" step="0.1" class="form-control" id="temperature" name="temperature" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="number" step="0.000001" class="form-control" id="latitude" name="latitude">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="number" step="0.000001" class="form-control" id="longitude" name="longitude">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="feels_like" class="form-label">Terasa Seperti (°C)</label>
                            <input type="number" step="0.1" class="form-control" id="feels_like" name="feels_like">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="humidity" class="form-label">Kelembaban (%)</label>
                            <input type="number" step="0.1" class="form-control" id="humidity" name="humidity">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pressure" class="form-label">Tekanan (hPa)</label>
                            <input type="number" step="0.1" class="form-control" id="pressure" name="pressure">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="wind_speed" class="form-label">Kecepatan Angin (km/h)</label>
                            <input type="number" step="0.1" class="form-control" id="wind_speed" name="wind_speed">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="wind_direction" class="form-label">Arah Angin (°)</label>
                            <input type="number" step="0.1" class="form-control" id="wind_direction" name="wind_direction">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="condition" class="form-label">Kondisi</label>
                            <select class="form-select" id="condition" name="condition">
                                <option value="">Pilih Kondisi</option>
                                <option value="Clear">Clear</option>
                                <option value="Clouds">Clouds</option>
                                <option value="Rain">Rain</option>
                                <option value="Drizzle">Drizzle</option>
                                <option value="Thunderstorm">Thunderstorm</option>
                                <option value="Snow">Snow</option>
                                <option value="Mist">Mist</option>
                                <option value="Fog">Fog</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="icon" class="form-label">Icon</label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="Contoh: 01d">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="uv_index" class="form-label">UV Index</label>
                            <input type="number" step="0.1" class="form-control" id="uv_index" name="uv_index">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="visibility" class="form-label">Visibilitas (m)</label>
                            <input type="number" step="0.1" class="form-control" id="visibility" name="visibility">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="recorded_at" class="form-label">Tanggal & Waktu</label>
                        <input type="datetime-local" class="form-control" id="recorded_at" name="recorded_at" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>

<script>
function resetForm() {
    document.getElementById('weatherForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Data Cuaca';
    document.getElementById('recorded_at').value = '<?php echo date('Y-m-d\TH:i'); ?>';
}

function editWeather(id) {
    // Fetch weather data via AJAX or redirect
    window.location.href = '?edit=' + id;
}

<?php if ($edit_data): ?>
// Auto open modal and fill form when editing
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('weatherModal'));
    modal.show();
    
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = '<?php echo $edit_data['id']; ?>';
    document.getElementById('modalTitle').textContent = 'Edit Data Cuaca';
    document.getElementById('location').value = '<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>';
    document.getElementById('temperature').value = '<?php echo htmlspecialchars($edit_data['temperature'] ?? ''); ?>';
    document.getElementById('latitude').value = '<?php echo htmlspecialchars($edit_data['latitude'] ?? ''); ?>';
    document.getElementById('longitude').value = '<?php echo htmlspecialchars($edit_data['longitude'] ?? ''); ?>';
    document.getElementById('feels_like').value = '<?php echo htmlspecialchars($edit_data['feels_like'] ?? ''); ?>';
    document.getElementById('humidity').value = '<?php echo htmlspecialchars($edit_data['humidity'] ?? ''); ?>';
    document.getElementById('pressure').value = '<?php echo htmlspecialchars($edit_data['pressure'] ?? ''); ?>';
    document.getElementById('wind_speed').value = '<?php echo htmlspecialchars($edit_data['wind_speed'] ?? ''); ?>';
    document.getElementById('wind_direction').value = '<?php echo htmlspecialchars($edit_data['wind_direction'] ?? ''); ?>';
    document.getElementById('condition').value = '<?php echo htmlspecialchars($edit_data['condition'] ?? ''); ?>';
    document.getElementById('description').value = '<?php echo htmlspecialchars($edit_data['description'] ?? ''); ?>';
    document.getElementById('icon').value = '<?php echo htmlspecialchars($edit_data['icon'] ?? ''); ?>';
    document.getElementById('uv_index').value = '<?php echo htmlspecialchars($edit_data['uv_index'] ?? ''); ?>';
    document.getElementById('visibility').value = '<?php echo htmlspecialchars($edit_data['visibility'] ?? ''); ?>';
    <?php if ($edit_data['recorded_at']): ?>
    const recordedDate = new Date('<?php echo $edit_data['recorded_at']; ?>');
    document.getElementById('recorded_at').value = recordedDate.toISOString().slice(0, 16);
    <?php endif; ?>
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>

