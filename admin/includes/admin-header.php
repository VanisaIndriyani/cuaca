<?php
// Set page title if not set
if (!isset($page_title)) {
    $page_title = 'Admin Panel';
}
?>

<!-- Mobile Header -->
<div class="mobile-admin-header">
    <div class="mobile-admin-header-content">
        <h1>
            <i class="bi bi-<?php echo isset($page_icon) ? $page_icon : 'gear'; ?>"></i>
            <?php echo htmlspecialchars($page_title); ?>
        </h1>
        <div class="mobile-admin-header-actions">
            <button class="mobile-admin-menu-btn" onclick="toggleMobileAdminSidebar()">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>
</div>

