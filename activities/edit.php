<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/Activity.php';

requireLogin();

$page_title = 'Edit Aktivitas';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$activity = new Activity($db);
$id = $_GET['id'] ?? 0;
$act = $activity->getById($id);

if (!$act) {
    redirect('activities/index.php');
}

// Check ownership (unless admin)
if (!isAdmin() && $act['user_id'] != $user_id) {
    redirect('activities/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity->id = $id;
    $activity->title = $_POST['title'] ?? '';
    $activity->description = $_POST['description'] ?? '';
    $activity->category = $_POST['category'] ?? '';
    $activity->activity_date = $_POST['activity_date'] ?? '';
    $activity->start_time = $_POST['start_time'] ?? '';
    $activity->end_time = $_POST['end_time'] ?? '';
    $activity->location = $_POST['location'] ?? '';

    if (empty($activity->title) || empty($activity->category)) {
        $error = 'Judul dan kategori harus diisi';
    } else {
        if ($activity->update()) {
            $success = 'Aktivitas berhasil diupdate';
            header('refresh:1;url=' . base_url('activities/index.php'));
        } else {
            $error = 'Gagal mengupdate aktivitas';
        }
    }
} else {
    $act = $activity->getById($id);
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
        <h4><i class="bi bi-pencil"></i> Edit Aktivitas</h4>
        <a href="<?php echo base_url('activities/index.php'); ?>" style="color: white; text-decoration: none;">
            <i class="bi bi-x-lg"></i>
        </a>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-none d-md-block">
                    <h4 class="mb-0">Edit Aktivitas</h4>
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
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($act['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($act['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Kategori *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="olahraga" <?php echo $act['category'] === 'olahraga' ? 'selected' : ''; ?>>Olahraga</option>
                                    <option value="pendidikan" <?php echo $act['category'] === 'pendidikan' ? 'selected' : ''; ?>>Pendidikan</option>
                                    <option value="kerja" <?php echo $act['category'] === 'kerja' ? 'selected' : ''; ?>>Kerja</option>
                                    <option value="istirahat" <?php echo $act['category'] === 'istirahat' ? 'selected' : ''; ?>>Istirahat</option>
                                    <option value="lainnya" <?php echo $act['category'] === 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="activity_date" class="form-label">Tanggal *</label>
                                <input type="date" class="form-control" id="activity_date" name="activity_date" value="<?php echo $act['activity_date']; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Waktu Mulai</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo $act['start_time']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">Waktu Selesai</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo $act['end_time']; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Lokasi</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($act['location'] ?? ''); ?>">
                                <button type="button" class="btn btn-outline-primary" id="getLocationBtn" onclick="getCurrentLocation()">
                                    <i class="bi bi-geo-alt-fill"></i> Update Lokasi
                                </button>
                            </div>
                            <small class="text-muted" id="locationStatus">Klik tombol untuk memperbarui lokasi saat ini</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update
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

<script>
// Fungsi untuk mendapatkan lokasi saat ini
function getCurrentLocation() {
    const locationInput = document.getElementById('location');
    const locationStatus = document.getElementById('locationStatus');
    const getLocationBtn = document.getElementById('getLocationBtn');
    
    // Cek apakah browser mendukung geolocation
    if (!navigator.geolocation) {
        locationStatus.textContent = 'Browser Anda tidak mendukung geolocation';
        locationStatus.className = 'text-danger';
        return;
    }
    
    // Update UI
    locationStatus.textContent = 'Mendapatkan lokasi...';
    locationStatus.className = 'text-info';
    getLocationBtn.disabled = true;
    getLocationBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
    
    // Request lokasi
    navigator.geolocation.getCurrentPosition(
        async function(position) {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            
            try {
                // Reverse geocoding menggunakan Nominatim (OpenStreetMap) - gratis, tidak perlu API key
                locationStatus.textContent = 'Mengonversi koordinat ke alamat...';
                
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
                    {
                        headers: {
                            'User-Agent': 'CuacaApp/1.0'
                        }
                    }
                );
                
                if (!response.ok) {
                    throw new Error('Gagal mendapatkan alamat');
                }
                
                const data = await response.json();
                
                // Format alamat dari hasil reverse geocoding
                let address = '';
                if (data.address) {
                    const addr = data.address;
                    // Prioritaskan nama jalan, kemudian kelurahan/kecamatan, kota
                    if (addr.road) {
                        address = addr.road;
                        if (addr.house_number) address = addr.house_number + ' ' + address;
                    } else if (addr.neighbourhood || addr.suburb) {
                        address = addr.neighbourhood || addr.suburb;
                    } else if (addr.village || addr.town || addr.city) {
                        address = addr.village || addr.town || addr.city;
                    }
                    
                    // Tambahkan informasi tambahan
                    const parts = [];
                    if (addr.village && !address.includes(addr.village)) parts.push(addr.village);
                    if (addr.subdistrict) parts.push(addr.subdistrict);
                    if (addr.city && !address.includes(addr.city)) parts.push(addr.city);
                    if (addr.state) parts.push(addr.state);
                    
                    if (parts.length > 0) {
                        address += (address ? ', ' : '') + parts.join(', ');
                    }
                    
                    // Jika tidak ada alamat yang detail, gunakan display_name
                    if (!address && data.display_name) {
                        address = data.display_name.split(',').slice(0, 3).join(',').trim();
                    }
                }
                
                // Jika masih kosong, gunakan koordinat
                if (!address) {
                    address = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                }
                
                // Isi field lokasi
                locationInput.value = address;
                locationStatus.textContent = 'Lokasi berhasil diperbarui!';
                locationStatus.className = 'text-success';
                
            } catch (error) {
                console.error('Error reverse geocoding:', error);
                // Jika reverse geocoding gagal, gunakan koordinat
                locationInput.value = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                locationStatus.textContent = 'Lokasi diperbarui (koordinat). Anda dapat mengedit untuk menambahkan nama tempat.';
                locationStatus.className = 'text-warning';
            } finally {
                getLocationBtn.disabled = false;
                getLocationBtn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Update Lokasi';
            }
        },
        function(error) {
            // Handle error
            let errorMessage = 'Gagal mendapatkan lokasi. ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += 'Akses lokasi ditolak. Silakan izinkan akses lokasi di pengaturan browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Informasi lokasi tidak tersedia.';
                    break;
                case error.TIMEOUT:
                    errorMessage += 'Waktu permintaan lokasi habis.';
                    break;
                default:
                    errorMessage += 'Terjadi kesalahan yang tidak diketahui.';
                    break;
            }
            
            locationStatus.textContent = errorMessage;
            locationStatus.className = 'text-danger';
            getLocationBtn.disabled = false;
            getLocationBtn.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Coba Lagi';
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}
</script>

<?php include '../includes/footer.php'; ?>

