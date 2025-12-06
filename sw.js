// Service Worker for Web Push Notifications
self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Notifikasi';
    const options = {
        body: data.message || 'Anda memiliki notifikasi baru',
        icon: '/cuaca/assets/images/icon-192x192.png',
        badge: '/cuaca/assets/images/badge-72x72.png',
        data: data.url || '/cuaca/dashboard.php'
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data || '/cuaca/dashboard.php')
    );
});

