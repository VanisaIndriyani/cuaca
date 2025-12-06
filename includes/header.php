<?php
if (!isset($page_title)) {
    $page_title = APP_NAME;
}
$current_hour = (int)date('H');
$is_night = $current_hour >= 18 || $current_hour < 6;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <style>
        :root {
            --primary-color: <?php echo $is_night ? '#1a1a2e' : '#3b82f6'; ?>;
            --secondary-color: <?php echo $is_night ? '#16213e' : '#60a5fa'; ?>;
            --bg-color: <?php echo $is_night ? '#0f0f1e' : '#f0f9ff'; ?>;
            --card-bg: <?php echo $is_night ? '#1a1a2e' : '#ffffff'; ?>;
            --text-color: <?php echo $is_night ? '#e0e0e0' : '#1f2937'; ?>;
            --text-muted: <?php echo $is_night ? '#9ca3af' : '#6b7280'; ?>;
        }
        
        html, body {
            background: var(--bg-color);
            color: var(--text-color);
            transition: all 0.3s ease;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
            margin: 0;
            padding: 0;
        }
        
        * {
            box-sizing: border-box;
        }
        
        .navbar {
            background: var(--card-bg) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-color);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .sidebar {
            background: var(--card-bg);
            min-height: calc(100vh - 56px);
            padding: 1rem;
        }
        
        .sidebar .nav-link {
            color: var(--text-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar-modern">
        <div class="navbar-content">
            <a class="navbar-brand-modern" href="<?php echo base_url('dashboard.php'); ?>">
                <i class="bi bi-cloud-sun"></i>
                <span><?php echo APP_NAME; ?></span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="navbar-actions">
                <?php 
                // Hide notification button on admin pages
                $is_admin_page = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
                if (!$is_admin_page): 
                ?>
                <button class="navbar-notification-btn" onclick="toggleNotificationDropdown()" title="Notifikasi" style="position: relative; border: none; background: rgba(255, 255, 255, 0.2); cursor: pointer; padding: 0.5rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; transition: all 0.3s; margin-right: 0.75rem;">
                    <i class="bi bi-bell-fill" style="color: #fbbf24; font-size: 1.25rem; display: block !important; visibility: visible !important;"></i>
                    <span class="navbar-notification-badge" id="navbarNotificationBadge" style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.7rem; font-weight: 700; padding: 3px 6px; border-radius: 12px; min-width: 18px; height: 18px; text-align: center; line-height: 1.2; display: none; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.2); border: 2px solid white;">0</span>
                </button>
                <?php endif; ?>
                <a href="<?php echo base_url('profile.php'); ?>" class="navbar-avatar-btn" title="Profile">
                    <?php 
                    $avatar_url = null;
                    if (isset($_SESSION['user_avatar']) && $_SESSION['user_avatar']) {
                        if (strpos($_SESSION['user_avatar'], 'http') === 0) {
                            $avatar_url = $_SESSION['user_avatar'];
                        } else {
                            $avatar_url = base_url('public' . $_SESSION['user_avatar']);
                        }
                    }
                    ?>
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="navbar-avatar-img">
                    <?php else: ?>
                        <div class="navbar-avatar-placeholder">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 2)); ?>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>

