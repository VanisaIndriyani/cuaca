// Main JavaScript file

// Store last known location for comparison
let lastKnownLocation = {
    lat: null,
    lon: null,
    timestamp: null
};

// Auto-detect location on page load after login
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in (check for navbar or user session indicator)
    const isLoggedIn = document.querySelector('.navbar-modern') !== null;
    
    // Only auto-request on specific pages (dashboard, weather, activities)
    const currentPage = window.location.pathname;
    const allowedPages = ['/cuaca/dashboard.php', '/cuaca/weather/index.php', '/cuaca/activities/index.php'];
    const isAllowedPage = allowedPages.some(page => currentPage.includes(page.split('/').pop()));
    
    if (isLoggedIn && isAllowedPage) {
        // Check if location permission was already requested
        const locationRequested = localStorage.getItem('locationRequested');
        const locationDenied = localStorage.getItem('locationDenied');
        
        // Check if location parameter is not set
        const urlParams = new URLSearchParams(window.location.search);
        const hasLocation = urlParams.has('location') || urlParams.has('lat') || urlParams.has('lon');
        
        // Store current location if available
        if (urlParams.has('lat') && urlParams.has('lon')) {
            lastKnownLocation.lat = parseFloat(urlParams.get('lat'));
            lastKnownLocation.lon = parseFloat(urlParams.get('lon'));
            lastKnownLocation.timestamp = Date.now();
        }
        
        // Auto-request location if:
        // 1. Not requested before OR
        // 2. User hasn't denied it permanently AND
        // 3. No location parameter in URL
        if (!hasLocation && !locationDenied && (!locationRequested || locationRequested === 'false')) {
            // Wait a bit for page to fully load
            setTimeout(function() {
                autoRequestLocation();
            }, 1000);
        }
        
        // Start auto-update location every 5 minutes (300000 ms)
        // Only if user has granted location permission
        if (!locationDenied && (hasLocation || locationRequested === 'true')) {
            setInterval(function() {
                autoUpdateLocation();
            }, 300000); // 5 minutes
        }
    }
});

// Auto request location permission
function autoRequestLocation() {
    if (!navigator.geolocation) {
        console.log('Geolocation tidak didukung browser');
        return;
    }
    
    // Mark as requested
    localStorage.setItem('locationRequested', 'true');
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            // Remove denied flag if user now allows
            localStorage.removeItem('locationDenied');
            
            // Store location
            lastKnownLocation.lat = lat;
            lastKnownLocation.lon = lon;
            lastKnownLocation.timestamp = Date.now();
            
            // Redirect to current page with location coordinates
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('lat', lat);
            currentUrl.searchParams.set('lon', lon);
            // Remove location param if exists (use coordinates instead)
            currentUrl.searchParams.delete('location');
            
            window.location.href = currentUrl.toString();
        },
        function(error) {
            console.log('Geolocation error:', error);
            
            // If user denied permission, remember it
            if (error.code === error.PERMISSION_DENIED) {
                localStorage.setItem('locationDenied', 'true');
                console.log('User menolak akses lokasi');
            }
            
            // Continue with default location (Jakarta or session location)
            // Don't show error to user, just use default silently
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000 // Cache for 5 minutes
        }
    );
}

// Auto-update location periodically (check if location changed)
function autoUpdateLocation() {
    if (!navigator.geolocation) {
        return;
    }
    
    const locationDenied = localStorage.getItem('locationDenied');
    if (locationDenied === 'true') {
        return; // Don't try if user denied
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            // Check if location changed significantly (more than 100 meters)
            if (lastKnownLocation.lat && lastKnownLocation.lon) {
                const distance = calculateDistance(
                    lastKnownLocation.lat, 
                    lastKnownLocation.lon, 
                    lat, 
                    lon
                );
                
                // If moved more than 100 meters, update location
                if (distance > 0.1) { // 0.1 km = 100 meters
                    console.log('Lokasi berubah, update lokasi...');
                    
                    // Update stored location
                    lastKnownLocation.lat = lat;
                    lastKnownLocation.lon = lon;
                    lastKnownLocation.timestamp = Date.now();
                    
                    // Update URL and reload
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('lat', lat);
                    currentUrl.searchParams.set('lon', lon);
                    currentUrl.searchParams.delete('location');
                    
                    // Reload page to get updated weather data
                    window.location.href = currentUrl.toString();
                } else {
                    // Location hasn't changed much, just update timestamp
                    lastKnownLocation.timestamp = Date.now();
                }
            } else {
                // First time getting location
                lastKnownLocation.lat = lat;
                lastKnownLocation.lon = lon;
                lastKnownLocation.timestamp = Date.now();
            }
        },
        function(error) {
            console.log('Auto-update location error:', error);
            // Silent fail - don't disturb user
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000 // Accept cached location up to 1 minute old
        }
    );
}

// Calculate distance between two coordinates in kilometers (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radius of the Earth in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

// Get current location (manual trigger)
function getCurrentLocation() {
    if (navigator.geolocation) {
        // Show loading indicator if button exists
        const btn = event?.target?.closest('button') || event?.target?.closest('a');
        if (btn) {
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memuat...';
            btn.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    window.location.href = window.location.pathname + '?lat=' + lat + '&lon=' + lon;
                },
                function(error) {
                    if (btn) {
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    }
                    
                    let errorMsg = 'Tidak dapat mengambil lokasi. ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += 'Akses lokasi ditolak. Izinkan akses lokasi di pengaturan browser.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += 'Informasi lokasi tidak tersedia.';
                            break;
                        case error.TIMEOUT:
                            errorMsg += 'Waktu permintaan lokasi habis.';
                            break;
                        default:
                            errorMsg += error.message;
                            break;
                    }
                    alert(errorMsg);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            // Fallback if no button
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    window.location.href = window.location.pathname + '?lat=' + lat + '&lon=' + lon;
                },
                function(error) {
                    console.error('Geolocation error:', error);
                }
            );
        }
    }
}

// Service Worker Registration for Web Push
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/cuaca/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registered');
            })
            .catch(function(error) {
                console.log('ServiceWorker registration failed');
            });
    });
}

