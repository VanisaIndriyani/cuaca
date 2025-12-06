// Notification functions - Shared across all pages
let notificationDropdown = null;

function toggleNotificationDropdown() {
    if (!notificationDropdown) {
        createNotificationDropdown();
    }
    notificationDropdown.classList.toggle('show');
    if (notificationDropdown.classList.contains('show')) {
        loadNotifications();
    }
}

function createNotificationDropdown() {
    notificationDropdown = document.createElement('div');
    notificationDropdown.id = 'notificationDropdown';
    notificationDropdown.className = 'notification-dropdown';
    notificationDropdown.innerHTML = `
        <div class="notification-header">
            <h5>Notifikasi</h5>
            <button onclick="markAllNotificationsRead()" class="btn-mark-all-read">Tandai semua sudah dibaca</button>
        </div>
        <div class="notification-list" id="notificationList">
            <div class="notification-loading">Memuat notifikasi...</div>
        </div>
    `;
    document.body.appendChild(notificationDropdown);
}

function loadNotifications() {
    fetch(baseUrl + 'api/notifications.php?action=list')
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error('Response is not JSON: ' + text.substring(0, 100));
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
                updateNotificationBadge();
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            const list = document.getElementById('notificationList');
            if (list) {
                list.innerHTML = '<div class="notification-empty">Gagal memuat notifikasi</div>';
            }
        });
}

function displayNotifications(notifications) {
    const list = document.getElementById('notificationList');
    if (!list) return;
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="notification-empty">Tidak ada notifikasi</div>';
        return;
    }
    
    list.innerHTML = notifications.map(notif => `
        <div class="notification-item ${!notif.read_at ? 'unread' : ''}" onclick="markNotificationRead(${notif.id})">
            <div class="notification-icon">
                ${notif.type === 'warning' ? '⚠️' : notif.type === 'info' ? '📅' : 'ℹ️'}
            </div>
            <div class="notification-content">
                <div class="notification-title">${notif.title}</div>
                <div class="notification-message">${notif.message}</div>
                <div class="notification-time">${formatNotificationTime(notif.created_at)}</div>
            </div>
        </div>
    `).join('');
}

function formatNotificationTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Baru saja';
    if (minutes < 60) return `${minutes} menit yang lalu`;
    if (hours < 24) return `${hours} jam yang lalu`;
    if (days < 7) return `${days} hari yang lalu`;
    return date.toLocaleDateString('id-ID');
}

function markNotificationRead(notificationId) {
    fetch(baseUrl + 'api/notifications.php?action=mark_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('Response is not JSON');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateNotificationBadge();
            loadNotifications();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllNotificationsRead() {
    fetch(baseUrl + 'api/notifications.php?action=mark_all_read')
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error('Response is not JSON');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateNotificationBadge();
                loadNotifications();
                showSuccessToast('Semua notifikasi telah ditandai sebagai sudah dibaca');
            } else {
                showErrorToast('Gagal menandai semua notifikasi sebagai sudah dibaca');
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
            showErrorToast('Terjadi kesalahan saat menandai notifikasi');
        });
}

function updateNotificationBadge() {
    fetch(baseUrl + 'api/notifications.php?action=unread_count')
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Badge update - Response is not JSON:', text.substring(0, 200));
                    throw new Error('Response is not JSON');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Badge update response:', data); // Debug log
            if (data.success !== false && typeof data.count !== 'undefined') {
                const count = parseInt(data.count) || 0;
                const badges = document.querySelectorAll('.notification-badge-mobile, .notification-badge-desktop, .notification-badge-sidebar, .notification-badge-mobile-menu, .navbar-notification-badge, #navbarNotificationBadge');
                
                console.log('Found badges:', badges.length, 'Count:', count); // Debug log
                
                badges.forEach(badge => {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count.toString();
                        badge.style.display = 'block';
                        badge.style.visibility = 'visible';
                        badge.style.opacity = '1';
                        badge.classList.add('show');
                        // Force show with inline styles
                        badge.setAttribute('style', 
                            'position: absolute !important; ' +
                            'top: -5px !important; ' +
                            'right: -5px !important; ' +
                            'background: #ef4444 !important; ' +
                            'color: white !important; ' +
                            'font-size: 0.7rem !important; ' +
                            'font-weight: 700 !important; ' +
                            'padding: 3px 6px !important; ' +
                            'border-radius: 12px !important; ' +
                            'min-width: 18px !important; ' +
                            'height: 18px !important; ' +
                            'text-align: center !important; ' +
                            'line-height: 1.2 !important; ' +
                            'border: 2px solid white !important; ' +
                            'box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4) !important; ' +
                            'z-index: 1000 !important; ' +
                            'display: block !important; ' +
                            'visibility: visible !important; ' +
                            'opacity: 1 !important;'
                        );
                        console.log('Badge shown with count:', count); // Debug log
                    } else {
                        badge.style.display = 'none';
                        badge.style.visibility = 'hidden';
                        badge.style.opacity = '0';
                        badge.classList.remove('show');
                    }
                });
            } else {
                console.error('Badge update - Invalid response:', data);
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
        });
}

// Toast notification functions
function showSuccessToast(message) {
    showToast(message, 'success');
}

function showErrorToast(message) {
    showToast(message, 'error');
}

function showToast(message, type = 'success') {
    // Remove existing toast if any
    const existingToast = document.querySelector('.notification-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'notification-toast notification-toast-' + type;
    toast.innerHTML = `
        <div class="notification-toast-content">
            <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles if not already added
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .notification-toast {
                position: fixed;
                top: 80px;
                right: 20px;
                background: white;
                padding: 1rem 1.25rem;
                border-radius: 12px;
                box-shadow: 0 4px 16px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideInRight 0.3s ease-out;
                min-width: 300px;
                max-width: 400px;
            }
            
            .notification-toast-success {
                border-left: 4px solid #10b981;
            }
            
            .notification-toast-error {
                border-left: 4px solid #ef4444;
            }
            
            .notification-toast-content {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                color: var(--text-color);
            }
            
            .notification-toast-content i {
                font-size: 1.25rem;
            }
            
            .notification-toast-success .notification-toast-content i {
                color: #10b981;
            }
            
            .notification-toast-error .notification-toast-content i {
                color: #ef4444;
            }
            
            .notification-toast-content span {
                flex: 1;
                font-size: 0.9rem;
                font-weight: 500;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 3000);
}

// Get base URL
const baseUrl = window.location.origin + '/cuaca/';

// Load notification badge on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update immediately
    updateNotificationBadge();
    
    // Also update after a short delay to ensure DOM is ready
    setTimeout(updateNotificationBadge, 500);
    
    // Update badge every 5 seconds (lebih sering untuk catch notifikasi baru dari admin)
    setInterval(updateNotificationBadge, 5000);
    
    // Also update when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateNotificationBadge();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const notificationBtn = document.querySelector('.notification-btn-desktop, .notification-btn-mobile, .notification-btn-sidebar, .notification-btn-mobile-menu, .navbar-notification-btn');
        const dropdown = document.getElementById('notificationDropdown');
        
        if (dropdown && notificationBtn && !notificationBtn.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
});

