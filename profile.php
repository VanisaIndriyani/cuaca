<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Models/User.php';

requireLogin();

$page_title = 'Profile';
$page_icon = 'person';
$is_admin = isAdmin();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$user = new User($db);
$user_data = $user->getById($user_id);

// Handle success message from redirect
if (isset($_GET['updated'])) {
    $success = 'Foto profil berhasil diupdate!';
    // Refresh user data
    $user_data = $user->getById($user_id);
}

// Create uploads directory if not exists
$upload_dir = __DIR__ . '/public/uploads/avatars/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user->id = $user_id;
    $user->name = $_POST['name'] ?? $user_data['name'];
    $user->email = $_POST['email'] ?? $user_data['email'];
    
    // Handle avatar upload (can be uploaded separately or with other data)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Validate file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $error = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
        } elseif ($file['size'] > $max_size) {
            $error = 'Ukuran file terlalu besar. Maksimal 2MB.';
        } else {
            // Generate unique filename
            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Create directory if not exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old avatar if exists (only local files, not Google avatars)
                if (!empty($user_data['avatar']) && strpos($user_data['avatar'], '/uploads/avatars/') !== false) {
                    $old_avatar_path = __DIR__ . '/public' . $user_data['avatar'];
                    if (file_exists($old_avatar_path)) {
                        @unlink($old_avatar_path);
                    }
                }
                
                // Set avatar path
                $user->avatar = '/uploads/avatars/' . $new_filename;
                
                // Update avatar in database (with current name and email)
                if ($user->update()) {
                    // Update session immediately
                    $avatar_full_url = base_url('public' . $user->avatar);
                    $_SESSION['user_avatar'] = $avatar_full_url;
                    $success = 'Foto profil berhasil diupdate!';
                    // Refresh user data to show new avatar
                    $user_data = $user->getById($user_id);
                    // Force refresh to show new avatar
                    header('Location: ' . base_url('profile.php?updated=1'));
                    exit;
                } else {
                    $error = 'Gagal menyimpan foto ke database';
                }
            } else {
                $error = 'Gagal mengupload foto. Pastikan folder uploads memiliki permission yang benar.';
            }
        }
    }
    
    // Update profile data (name and email) - only if no avatar upload or avatar upload failed
    if (empty($error) && (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK)) {
        if (empty($user->name) || empty($user->email)) {
            $error = 'Nama dan email harus diisi';
        } else {
            if ($user->update()) {
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_email'] = $user->email;
                if (!empty($user->avatar)) {
                    if (strpos($user->avatar, 'http') === 0) {
                        $_SESSION['user_avatar'] = $user->avatar;
                    } else {
                        $_SESSION['user_avatar'] = base_url('public' . $user->avatar);
                    }
                }
                if (empty($success)) {
                    $success = 'Profile berhasil diupdate';
                }
                // Refresh user data
                $user_data = $user->getById($user_id);
            } else {
                $error = 'Gagal mengupdate profile';
            }
        }
    } elseif (empty($error) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        // If avatar was uploaded successfully, also update name/email if provided
        if (!empty($_POST['name']) && !empty($_POST['email'])) {
            $user->name = $_POST['name'];
            $user->email = $_POST['email'];
            if ($user->update()) {
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_email'] = $user->email;
                // Refresh user data
                $user_data = $user->getById($user_id);
            }
        }
    }
}

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

.mobile-menu-item.logout-item {
    color: #dc2626;
}

.mobile-menu-item.logout-item:hover,
.mobile-menu-item.logout-item.active {
    color: #dc2626;
    background: rgba(220, 38, 38, 0.1);
}

/* Mobile Header */
.mobile-header {
    display: none;
    background: var(--primary-color);
    color: white;
    padding: 1rem;
    position: sticky;
    top: 56px;
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

.mobile-header-btn.logout-btn {
    background: rgba(220, 38, 38, 0.2);
}

.mobile-header-btn.logout-btn:hover {
    background: rgba(220, 38, 38, 0.3);
}

/* Profile Card Mobile */
.profile-card-mobile {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-avatar-section {
    text-align: center;
    padding: 2rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    position: relative;
}

.profile-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: 600;
    margin: 0 auto 1rem;
    border: 4px solid var(--primary-color);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    object-fit: cover;
    transition: none;
    transform: none;
}


.profile-name-large {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.profile-email-large {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.form-group-mobile {
    margin-bottom: 1.5rem;
}

.form-label-mobile {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    font-size: 0.9rem;
}

.form-control-mobile {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid rgba(0,0,0,0.1);
    border-radius: 12px;
    font-size: 1rem;
    background: var(--card-bg);
    color: var(--text-color);
    transition: all 0.3s;
    box-sizing: border-box;
}

.form-control-mobile:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control-mobile:disabled {
    background: rgba(0,0,0,0.05);
    cursor: not-allowed;
}

.btn-submit-mobile {
    width: 100%;
    padding: 1rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 1rem;
}

.btn-submit-mobile:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-logout-mobile {
    width: 100%;
    padding: 0.875rem 1.5rem;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1rem;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

.btn-logout-mobile:hover {
    background: #b91c1c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
    color: white;
}

.alert-mobile {
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.alert-danger-mobile {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-success-mobile {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.2);
}


/* Responsive */
@media (max-width: 768px) {
    .mobile-header {
        display: block;
    }
    
    .mobile-menu {
        display: block;
    }
    
    .container {
        padding: 0;
    }
    
    .card {
        border-radius: 0;
        margin: 0;
        border: none;
    }
    
    .card-header {
        display: none;
    }
    
    body {
        padding-bottom: 70px;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-8 {
        padding: 0;
    }
}

@media (min-width: 769px) {
    .mobile-header,
    .mobile-menu {
        display: none !important;
    }
    
    .profile-card-mobile {
        display: none;
    }
    
    /* Desktop Layout */
    .container {
        max-width: 100%;
        overflow-x: hidden;
        padding: 0;
    }
    
    .row {
        margin: 0;
        display: flex;
        min-height: calc(100vh - 56px);
        align-items: flex-start;
    }
    
    .profile-main-content {
        flex: 1;
        margin-left: 80px;
        margin-top: 56px;
        width: calc(100% - 80px);
        max-width: calc(100% - 80px);
        padding: 2rem;
        padding-top: 2.5rem;
        display: block;
        min-height: calc(100vh - 56px);
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .profile-main-content .card {
        width: 100%;
        max-width: 700px;
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        border: none;
        margin: 0 auto;
    }
    
    .profile-main-content .card-body {
        padding: 2.5rem !important;
    }
    
    .profile-main-content .text-center {
        margin-bottom: 2rem;
    }
    
    .form-control {
        transition: all 0.3s ease;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.75rem 1rem;
    }
    
    .form-control:hover {
        border-color: #3b82f6;
    }
    
    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        outline: none;
        transform: translateY(-2px);
    }
    
    .btn-primary {
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }
    
    .btn-outline-danger {
        transition: all 0.3s ease;
    }
    
    .btn-outline-danger:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    
    .form-control:hover {
        border-color: #3b82f6;
    }
    
    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        outline: none;
        transform: translateY(-2px);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }
    
    .btn-outline-danger:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
}

@media (min-width: 992px) {
    .profile-main-content {
        padding-left: 2.5rem;
        padding-right: 2.5rem;
        padding-top: 2.5rem;
    }
    
    .profile-main-content .card {
        max-width: 650px;
    }
}

@media (min-width: 1200px) {
    .container {
        max-width: 1600px;
        margin: 0 auto;
    }
    
    .profile-main-content {
        padding-left: 3rem;
        padding-right: 3rem;
        padding-top: 2.5rem;
    }
    
    .profile-main-content .card {
        max-width: 700px;
    }
}

/* Laptop specific styles (1024px - 1399px) */
@media (min-width: 1024px) and (max-width: 1399px) {
    .profile-main-content {
        padding-left: 2rem;
        padding-right: 2rem;
        padding-top: 2.5rem;
    }
    
    .profile-main-content .card {
        max-width: 600px;
    }
    
    .profile-main-content .card-body {
        padding: 2rem !important;
    }
}
</style>

<?php if ($is_admin): ?>
    <!-- Admin Layout -->
    <?php include 'admin/includes/admin-header.php'; ?>
    <?php include 'admin/includes/admin-sidebar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <main class="admin-main-content">
                <div class="admin-content-card">
<?php else: ?>
    <!-- User Layout -->
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="mobile-header-content">
            <h1><i class="bi bi-person"></i> Profile</h1>
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
                    <a href="<?php echo base_url('activities/index.php'); ?>" class="nav-item" title="Aktivitas">
                        <i class="bi bi-calendar-event"></i>
                    </a>
                    <a href="<?php echo base_url('weather/index.php'); ?>" class="nav-item" title="Cuaca">
                        <i class="bi bi-cloud-lightning"></i>
                    </a>
                    <a href="<?php echo base_url('analytics.php'); ?>" class="nav-item" title="Analitik">
                        <i class="bi bi-graph-up"></i>
                    </a>
                    <a href="<?php echo base_url('profile.php'); ?>" class="nav-item active" title="Profile">
                        <i class="bi bi-person"></i>
                    </a>
                    <a href="<?php echo base_url('auth/logout.php'); ?>" class="nav-item" title="Logout" onclick="return confirmLogout()">
                        <i class="bi bi-power"></i>
                    </a>
                </nav>
            </aside>

            <div class="col-md-8 profile-main-content">
<?php endif; ?>
                <?php if ($is_admin): ?>
                    <div class="admin-content-card">
                        <h2>
                            <i class="bi bi-person"></i>
                            Profile
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
                <?php endif; ?>
                
                <!-- Mobile Profile Card -->
                <div class="<?php echo $is_admin ? 'd-md-none' : 'profile-card-mobile d-md-none'; ?>">
                <div class="profile-avatar-section">
                    <?php 
                    $avatar_url = '';
                    if (!empty($user_data['avatar'])) {
                        if (strpos($user_data['avatar'], 'http') === 0) {
                            $avatar_url = $user_data['avatar'];
                        } else {
                            // Ensure path starts with /
                            $avatar_path = $user_data['avatar'];
                            if (strpos($avatar_path, '/') !== 0) {
                                $avatar_path = '/' . $avatar_path;
                            }
                            $avatar_url = base_url('public' . $avatar_path);
                        }
                    }
                    ?>
                    <?php if ($avatar_url && !empty($user_data['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($avatar_url); ?>?v=<?php echo time(); ?>" alt="Avatar" class="profile-avatar-large" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="profile-avatar-large" style="display: none;">
                            <?php echo strtoupper(substr($user_data['name'], 0, 2)); ?>
                        </div>
                    <?php else: ?>
                        <div class="profile-avatar-large">
                            <?php echo strtoupper(substr($user_data['name'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="profile-name-large"><?php echo htmlspecialchars($user_data['name']); ?></div>
                    <div class="profile-email-large"><?php echo htmlspecialchars($user_data['email']); ?></div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert-mobile alert-danger-mobile">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-mobile alert-success-mobile">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="form_mobile" enctype="multipart/form-data">
                    <input type="hidden" name="name" id="name_hidden_mobile" value="<?php echo htmlspecialchars($user_data['name']); ?>">
                    <input type="hidden" name="email" id="email_hidden_mobile" value="<?php echo htmlspecialchars($user_data['email']); ?>">
                    <div class="form-group-mobile">
                        <label for="name_mobile" class="form-label-mobile">
                            <i class="bi bi-person"></i> Nama
                        </label>
                        <input type="text" class="form-control-mobile" id="name_mobile" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                    </div>
                    <div class="form-group-mobile">
                        <label for="email_mobile" class="form-label-mobile">
                            <i class="bi bi-envelope"></i> Email
                        </label>
                        <input type="email" class="form-control-mobile" id="email_mobile" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    <div class="form-group-mobile">
                        <label class="form-label-mobile">
                            <i class="bi bi-shield-check"></i> Role
                        </label>
                        <input type="text" class="form-control-mobile" value="<?php echo htmlspecialchars(ucfirst(displayRole($user_data['role']))); ?>" disabled>
                    </div>
                    <div class="form-group-mobile">
                        <label class="form-label-mobile">
                            <i class="bi bi-calendar"></i> Bergabung
                        </label>
                        <input type="text" class="form-control-mobile" value="<?php echo date('d F Y', strtotime($user_data['created_at'])); ?>" disabled>
                    </div>
                    <button type="submit" class="btn-submit-mobile">
                        <i class="bi bi-check-circle"></i> Update Profile
                    </button>
                    <a href="<?php echo base_url('auth/logout.php'); ?>" class="btn-logout-mobile" onclick="return confirmLogout()">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </form>
                </div>
                
                <?php if (!$is_admin): ?>
                <!-- Desktop Profile Card -->
                <div class="card d-none d-md-block">
                <div class="card-body" style="padding: 2.5rem;">
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
                    
                    <div class="text-center mb-4">
                        <div class="avatar-upload-section" style="position: relative; display: inline-block;">
                            <?php 
                            $avatar_url = '';
                            if (!empty($user_data['avatar'])) {
                                if (strpos($user_data['avatar'], 'http') === 0) {
                                    $avatar_url = $user_data['avatar'];
                                } else {
                                    // Ensure path starts with /
                                    $avatar_path = $user_data['avatar'];
                                    if (strpos($avatar_path, '/') !== 0) {
                                        $avatar_path = '/' . $avatar_path;
                                    }
                                    $avatar_url = base_url('public' . $avatar_path);
                                }
                            }
                            ?>
                            <?php if ($avatar_url && !empty($user_data['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($avatar_url); ?>?v=<?php echo time(); ?>" alt="Avatar" class="rounded-circle mb-3" style="width: 140px; height: 140px; object-fit: cover; border: 4px solid #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: none; transform: none;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 140px; height: 140px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; font-size: 3.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); display: none;">
                                    <?php echo strtoupper(substr($user_data['name'], 0, 2)); ?>
                                </div>
                            <?php else: ?>
                                <div class="rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 140px; height: 140px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; font-size: 3.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    <?php echo strtoupper(substr($user_data['name'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="mb-2" style="font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>
                    
                    <form method="POST" id="form_desktop" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="name" class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="bi bi-person" style="color: #3b82f6;"></i> Nama
                            </label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; transition: all 0.3s;">
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="bi bi-envelope" style="color: #3b82f6;"></i> Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; transition: all 0.3s;">
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="bi bi-shield-check" style="color: #3b82f6;"></i> Role
                            </label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst(displayRole($user_data['role']))); ?>" disabled style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; background: #f9fafb;">
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="bi bi-calendar" style="color: #3b82f6;"></i> Bergabung
                            </label>
                            <input type="text" class="form-control" value="<?php echo date('d F Y', strtotime($user_data['created_at'])); ?>" disabled style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; background: #f9fafb;">
                        </div>
                        <button type="submit" class="btn btn-primary w-100" style="padding: 0.875rem; font-size: 1rem; font-weight: 600; border-radius: 10px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                        <a href="<?php echo base_url('auth/logout.php'); ?>" class="btn btn-outline-danger w-100 mt-3" onclick="return confirmLogout()" style="padding: 0.875rem; font-size: 1rem; font-weight: 600; border-radius: 10px; border: 2px solid #dc2626; color: #dc2626;">
                            <i class="bi bi-power"></i> Logout
                        </a>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if ($is_admin): ?>
                    <!-- Desktop Profile Card for Admin -->
                    <div class="card d-none d-md-block">
                        <div class="card-body" style="padding: 2.5rem;">
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
                            
                            <div class="text-center mb-4">
                                <div class="avatar-upload-section" style="position: relative; display: inline-block;">
                                    <?php 
                                    $avatar_url = '';
                                    if (!empty($user_data['avatar'])) {
                                        if (strpos($user_data['avatar'], 'http') === 0) {
                                            $avatar_url = $user_data['avatar'];
                                        } else {
                                            // Ensure path starts with /
                                            $avatar_path = $user_data['avatar'];
                                            if (strpos($avatar_path, '/') !== 0) {
                                                $avatar_path = '/' . $avatar_path;
                                            }
                                            $avatar_url = base_url('public' . $avatar_path);
                                        }
                                    }
                                    ?>
                                    <?php if ($avatar_url && !empty($user_data['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($avatar_url); ?>?v=<?php echo time(); ?>" alt="Avatar" class="rounded-circle mb-3" style="width: 140px; height: 140px; object-fit: cover; border: 4px solid #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: none; transform: none;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 140px; height: 140px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; font-size: 3.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); display: none;">
                                            <?php echo strtoupper(substr($user_data['name'], 0, 2)); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 140px; height: 140px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; font-size: 3.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                            <?php echo strtoupper(substr($user_data['name'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h3 class="mb-2" style="font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                                <p class="text-muted mb-4"><?php echo htmlspecialchars($user_data['email']); ?></p>
                            </div>
                            
                            <form method="POST" id="form_desktop_admin" enctype="multipart/form-data">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>">
                                <div class="mb-4">
                                    <label for="name_admin" class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        <i class="bi bi-person" style="color: #3b82f6;"></i> Nama
                                    </label>
                                    <input type="text" class="form-control" id="name_admin" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; transition: all 0.3s;">
                                </div>
                                <div class="mb-4">
                                    <label for="email_admin" class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        <i class="bi bi-envelope" style="color: #3b82f6;"></i> Email
                                    </label>
                                    <input type="email" class="form-control" id="email_admin" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; transition: all 0.3s;">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        <i class="bi bi-shield-check" style="color: #3b82f6;"></i> Role
                                    </label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst(displayRole($user_data['role']))); ?>" disabled style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; background: #f9fafb;">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        <i class="bi bi-calendar" style="color: #3b82f6;"></i> Bergabung
                                    </label>
                                    <input type="text" class="form-control" value="<?php echo date('d F Y', strtotime($user_data['created_at'])); ?>" disabled style="padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; background: #f9fafb;">
                                </div>
                                <button type="submit" class="btn btn-primary w-100" style="padding: 0.875rem; font-size: 1rem; font-weight: 600; border-radius: 10px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    <i class="bi bi-check-circle"></i> Update Profile
                                </button>
                                <a href="<?php echo base_url('auth/logout.php'); ?>" class="btn btn-outline-danger w-100 mt-3" onclick="return confirmLogout()" style="padding: 0.875rem; font-size: 1rem; font-weight: 600; border-radius: 10px; border: 2px solid #dc2626; color: #dc2626;">
                                    <i class="bi bi-power"></i> Logout
                                </a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php if ($is_admin): ?>
                    </div>
                </main>
        </div>
    </div>
            <?php else: ?>
            </div>
        </div>
    </div>
</div>
            <?php endif; ?>

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
        <a href="<?php echo base_url('analytics.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Analitik</span>
        </a>
        <a href="<?php echo base_url('profile.php'); ?>" class="mobile-menu-item active">
            <i class="bi bi-person-fill"></i>
            <span>Profile</span>
        </a>
      
    </div>
</div>

<script>
function confirmLogout() {
    return confirm('Apakah Anda ingin keluar?');
}
</script>

<?php if ($is_admin): ?>
<script src="<?php echo base_url('admin/includes/admin-sidebar.js'); ?>"></script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
