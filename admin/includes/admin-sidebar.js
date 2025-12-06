// Admin Sidebar Toggle Functions

function toggleMobileAdminSidebar() {
    const sidebar = document.getElementById('mobileAdminSidebar');
    const overlay = document.getElementById('mobileAdminSidebarOverlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('mobileAdminSidebar');
        const overlay = document.getElementById('mobileAdminSidebarOverlay');
        const menuBtn = document.querySelector('.mobile-admin-menu-btn');
        
        if (window.innerWidth <= 768) {
            if (sidebar && sidebar.classList.contains('active')) {
                if (!sidebar.contains(event.target) && menuBtn && !menuBtn.contains(event.target)) {
                    toggleMobileAdminSidebar();
                }
            }
        }
    });
});

