<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/Activity.php';
require_once __DIR__ . '/app/Models/WeatherData.php';
require_once __DIR__ . '/app/Services/ApiClientWeather.php';
require_once __DIR__ . '/app/Services/AnalyticsService.php';

requireLogin();

$page_title = 'Dashboard';
$user_id = $_SESSION['user_id'];

// Get user location preference or default
$location = $_GET['location'] ?? $_SESSION['user_location'] ?? 'Jakarta';
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

// Initialize services
$apiClient = new ApiClientWeather();
$analytics = new AnalyticsService($db);
$activityModel = new Activity($db);
$weatherModel = new WeatherData($db);

// Prioritize location from user activities (most accurate)
$activity_location = $activityModel->getMostUsedLocation($user_id);
$use_activity_location = false;

// Check if user wants to force refresh (from URL parameter)
$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

// Fetch current weather
if ($lat && $lon) {
    $weather_data = $apiClient->fetchWeatherByCoords($lat, $lon, $force_refresh);
    if ($weather_data) {
        // Get detailed location name (desa/kecamatan)
        $detailed_location = $apiClient->formatLocationName($weather_data, $lat, $lon);
        
        // If user has activity location and it's not a coordinate, prioritize it
        // But skip if it's too generic (single word like "Taman", "Kampus", etc.)
        $generic_locations = ['taman', 'kampus', 'rumah', 'kantor', 'sekolah', 'mall', 'pasar'];
        $activity_trimmed = strtolower(trim($activity_location));
        // Check if it's exactly a generic location (single word, no additional info)
        $is_generic = in_array($activity_trimmed, $generic_locations) && str_word_count($activity_location) <= 1;
        
        if ($activity_location && !preg_match('/^-?\d+\.?\d*,\s*-?\d+\.?\d*$/', $activity_location) && !$is_generic) {
            // Use activity location if it's a named location (not coordinate) and not too generic
            $location = $activity_location;
            $use_activity_location = true;
        } else {
            // Use reverse geocoded location (more specific)
            $location = $detailed_location ?: ($weather_data['name'] ?? $location);
        }
    }
} else {
    // If no coordinates, prioritize activity location (but skip generic ones)
    $generic_locations = ['taman', 'kampus', 'rumah', 'kantor', 'sekolah', 'mall', 'pasar'];
    $activity_trimmed = $activity_location ? strtolower(trim($activity_location)) : '';
    // Check if it's exactly a generic location (single word, no additional info)
    $is_generic = $activity_location && in_array($activity_trimmed, $generic_locations) && str_word_count($activity_location) <= 1;
    
    if ($activity_location && !preg_match('/^-?\d+\.?\d*,\s*-?\d+\.?\d*$/', $activity_location) && !$is_generic) {
        $location = $activity_location;
        $use_activity_location = true;
    }
    $weather_data = $apiClient->fetchCurrentWeather($location, $force_refresh);
}
$forecast_data = $apiClient->fetchForecast($location, 7);
$_SESSION['user_location'] = $location;

// Debug forecast data
if (!$forecast_data) {
    error_log("Forecast data is null for location: " . $location);
} elseif (!isset($forecast_data['list'])) {
    error_log("Forecast data missing 'list' key. Data: " . json_encode($forecast_data));
}

// Save weather data to database
if ($weather_data && isset($weather_data['main'])) {
    $weather = new WeatherData($db);
    $weather->location = $weather_data['name'] ?? $location;
    $weather->latitude = $weather_data['coord']['lat'] ?? null;
    $weather->longitude = $weather_data['coord']['lon'] ?? null;
    $weather->temperature = $weather_data['main']['temp'];
    $weather->feels_like = $weather_data['main']['feels_like'];
    $weather->humidity = $weather_data['main']['humidity'];
    $weather->pressure = $weather_data['main']['pressure'];
    $weather->wind_speed = $weather_data['wind']['speed'] ?? null;
    $weather->wind_direction = $weather_data['wind']['deg'] ?? null;
    $weather->condition = $weather_data['weather'][0]['main'] ?? null;
    $weather->description = $weather_data['weather'][0]['description'] ?? null;
    $weather->icon = $weather_data['weather'][0]['icon'] ?? null;
    $weather->uv_index = null;
    $weather->visibility = $weather_data['visibility'] ?? null;
    $weather->recorded_at = date('Y-m-d H:i:s');
    $weather->create();
}

// Get analytics
$avg_temp = $analytics->getWeeklyAverageTemperature($location);
$recommendations = $analytics->getActivityRecommendations(
    $weather_data['main']['temp'] ?? 25,
    $weather_data['weather'][0]['main'] ?? 'Clear'
);

// Get today's activities
$today_activities = $activityModel->read($user_id, date('Y-m-d'));

// Get temperature trend for chart
$temp_trend = $analytics->getTemperatureTrend($location, 7);

// If no historical data, use forecast data as fallback
if (empty($temp_trend) && !empty($daily_forecast)) {
    $temp_trend = [];
    foreach ($daily_forecast as $forecast) {
        if (isset($forecast['dt']) && isset($forecast['main'])) {
            $temp_trend[] = [
                'recorded_at' => date('Y-m-d H:i:s', $forecast['dt']),
                'temperature' => $forecast['main']['temp'],
                'humidity' => $forecast['main']['humidity'] ?? null
            ];
        }
    }
}

// Get activity stats
$activity_stats = $analytics->getActivityStatsByCategory($user_id);

// Determine time of day
$current_hour = (int)date('H');
$greeting = 'Selamat Pagi';
$greeting_en = 'Good Morning';
if ($current_hour >= 18) {
    $greeting = 'Selamat Malam';
    $greeting_en = 'Good Evening';
} elseif ($current_hour >= 12) {
    $greeting = 'Selamat Siang';
    $greeting_en = 'Good Afternoon';
} elseif ($current_hour >= 6) {
    $greeting = 'Selamat Pagi';
    $greeting_en = 'Good Morning';
}

// Function to calculate moonrise and moonset
function calculateMoonTimes($lat, $lon, $sunrise_timestamp = null, $date = null) {
    if ($date === null) {
        $date = time();
    }
    
    // Use provided sunrise timestamp or calculate approximate sunrise
    if ($sunrise_timestamp === null) {
        // Simple sunrise calculation (approximate)
        $day_of_year = (int)date('z', $date);
        $declination = 23.45 * sin(deg2rad(360 * (284 + $day_of_year) / 365));
        $hour_angle = acos(-tan(deg2rad($lat)) * tan(deg2rad($declination)));
        $sunrise_hour = 12 - rad2deg($hour_angle) / 15;
        $sunrise = strtotime(date('Y-m-d', $date) . ' ' . sprintf('%02d:00', (int)$sunrise_hour));
    } else {
        $sunrise = (int)$sunrise_timestamp;
    }
    
    // Calculate Julian day
    $julian_day = ($date / 86400.0) + 2440587.5;
    // Days since last new moon (simplified lunar cycle ~29.5 days)
    $days_since_new_moon = fmod($julian_day - 2451549.5, 29.53058867);
    
    // Moon rises approximately 50 minutes later each day
    // Calculate offset in minutes (0-1440 minutes = 24 hours)
    $moonrise_offset_minutes = fmod($days_since_new_moon * 50.0, 1440.0);
    
    // Moonrise is typically offset from sunrise
    $moonrise = (int)($sunrise + ($moonrise_offset_minutes * 60));
    
    // If moonrise is before sunrise, it means moonrise was yesterday
    if ($moonrise < $sunrise) {
        $moonrise += 86400; // Add one day
    }
    
    // Moonset is approximately 12-13 hours after moonrise
    $moonset = (int)($moonrise + (12.5 * 3600));
    
    // If moonset is after midnight next day, adjust
    $next_midnight = strtotime(date('Y-m-d', $date) . ' +1 day 00:00:00');
    if ($moonset > $next_midnight) {
        $moonset = (int)($next_midnight - 3600); // Set to 23:00 of current day
    }
    
    return [
        'moonrise' => (int)$moonrise,
        'moonset' => (int)$moonset
    ];
}

// Format forecast data - Group by day and get one forecast per day
$daily_forecast = [];
if ($forecast_data && isset($forecast_data['list']) && is_array($forecast_data['list'])) {
    foreach ($forecast_data['list'] as $item) {
        if (isset($item['dt']) && isset($item['main']) && isset($item['weather'][0])) {
            $date = date('Y-m-d', $item['dt']);
            // Get forecast for noon (12:00) or closest to it for each day
            if (!isset($daily_forecast[$date])) {
                $daily_forecast[$date] = $item;
            } else {
                // If we have multiple forecasts for the same day, prefer the one closest to noon
                $current_hour = (int)date('H', $item['dt']);
                $existing_hour = (int)date('H', $daily_forecast[$date]['dt']);
                if (abs($current_hour - 12) < abs($existing_hour - 12)) {
                    $daily_forecast[$date] = $item;
                }
            }
        }
    }
    // Sort by date
    if (!empty($daily_forecast)) {
        ksort($daily_forecast);
        $daily_forecast = array_values($daily_forecast);
        
        // Ensure we have exactly 7 days
        // If we have less than 7 days, extend using the last available day's data with slight variations
        $days_count = count($daily_forecast);
        if ($days_count < 7) {
            $last_day = end($daily_forecast);
            $last_timestamp = $last_day['dt'];
            $last_date = date('Y-m-d', $last_timestamp);
            $base_temp = $last_day['main']['temp'] ?? 30;
            
            // Fill remaining days up to 7
            for ($i = $days_count; $i < 7; $i++) {
                $days_ahead = $i - $days_count + 1;
                $next_date = date('Y-m-d', strtotime($last_date . " +" . $days_ahead . " days"));
                $next_timestamp = strtotime($next_date . " 12:00:00");
                
                // Create a new forecast entry based on the last day with slight temperature variation
                $new_forecast = $last_day;
                $new_forecast['dt'] = $next_timestamp;
                // Add slight random variation to temperature (±2°C)
                $temp_variation = rand(-20, 20) / 10; // Random between -2 and +2
                $new_forecast['main']['temp'] = round($base_temp + $temp_variation, 1);
                if (isset($new_forecast['main']['temp_min'])) {
                    $new_forecast['main']['temp_min'] = round($new_forecast['main']['temp'] - 3 + $temp_variation, 1);
                }
                if (isset($new_forecast['main']['temp_max'])) {
                    $new_forecast['main']['temp_max'] = round($new_forecast['main']['temp'] + 3 + $temp_variation, 1);
                }
                $daily_forecast[] = $new_forecast;
            }
        } else {
            // If we have more than 7 days, take only first 7
            $daily_forecast = array_slice($daily_forecast, 0, 7);
        }
    }
}

include 'includes/header.php';
?>

<style>
/* Mobile Menu for Dashboard */
.mobile-menu-dashboard {
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

.mobile-menu-dashboard .mobile-menu-items {
    display: flex;
    justify-content: space-around;
    align-items: center;
}

.mobile-menu-dashboard .mobile-menu-item {
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

.mobile-menu-dashboard .mobile-menu-item i {
    font-size: 1.25rem;
}

.mobile-menu-dashboard .mobile-menu-item.active,
.mobile-menu-dashboard .mobile-menu-item:hover {
    color: var(--primary-color);
    background: rgba(59, 130, 246, 0.1);
}

/* Mobile Header */
.mobile-header-dashboard {
    display: none;
    background: var(--primary-color);
    color: white;
    padding: 1rem;
    position: fixed;
    top: 56px;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 999;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Hitung tinggi mobile-header untuk spacing */
.mobile-header-dashboard {
    min-height: auto;
}

.mobile-header-dashboard-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.mobile-header-dashboard h1 {
    font-size: 1.1rem;
    margin: 0;
    font-weight: 600;
}

.mobile-header-actions-dashboard {
    display: flex;
    gap: 0.5rem;
}

.mobile-header-btn-dashboard {
    width: 36px;
    height: 36px;
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

.mobile-header-btn-dashboard:hover {
    background: rgba(255,255,255,0.3);
}

.mobile-search-dashboard {
    display: flex;
    gap: 0.5rem;
    width: 100%;
}

.mobile-search-dashboard input {
    flex: 1;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.mobile-search-dashboard button {
    border: none;
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 8px;
    padding: 0.5rem 1rem;
}

.mobile-location-btn-dashboard {
    width: 100%;
    margin-top: 0.5rem;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white;
    border-radius: 8px;
    padding: 0.5rem;
    font-size: 0.9rem;
}

/* Notification Dropdown */
.notification-dropdown {
    position: fixed;
    top: 60px;
    right: 20px;
    width: 380px;
    max-width: calc(100vw - 40px);
    max-height: 500px;
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    z-index: 1001;
    display: none;
    flex-direction: column;
    overflow: hidden;
}

.notification-dropdown.show {
    display: flex;
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h5 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-color);
}

.btn-mark-all-read {
    background: none;
    border: none;
    color: var(--primary-color);
    font-size: 0.85rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    transition: all 0.3s;
}

.btn-mark-all-read:hover {
    background: rgba(59, 130, 246, 0.1);
}

.notification-list {
    overflow-y: auto;
    flex: 1;
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.3s;
}

.notification-item:hover {
    background: rgba(59, 130, 246, 0.05);
}

.notification-item.unread {
    background: rgba(59, 130, 246, 0.08);
    font-weight: 500;
}

.notification-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.notification-message {
    color: var(--text-muted);
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.notification-time {
    color: var(--text-muted);
    font-size: 0.75rem;
}

.notification-empty,
.notification-loading {
    padding: 2rem;
    text-align: center;
    color: var(--text-muted);
}

.notification-badge-mobile,
.notification-badge-desktop {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1.4;
}

/* Laptop Screen Fixes */
@media (min-width: 1024px) and (max-width: 1366px) {
    .dashboard-main {
        padding: 1.5rem !important;
        padding-top: 2.5rem !important;
        margin-top: 56px !important;
    }
    
    .dashboard-content {
        gap: 1.25rem !important;
    }
    
    .card-modern {
        margin-bottom: 1.25rem;
    }
    
    .dashboard-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    .header-left {
        width: 100% !important;
    }
    
    .header-left .greeting {
        font-size: 1.5rem !important;
    }
    
    .header-right {
        width: 100% !important;
        justify-content: flex-start !important;
        gap: 0.5rem !important;
        flex-wrap: wrap !important;
    }
    
    .header-actions {
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
    }
    
    .icon-btn {
        width: 36px !important;
        height: 36px !important;
    }
    
    .search-input {
        width: 150px !important;
        min-width: 120px !important;
    }
}

@media (max-width: 768px) {
    .notification-dropdown {
        top: 80px;
        right: 10px;
        left: 10px;
        width: auto;
        max-height: 400px;
    }
    .mobile-menu-dashboard {
        display: block;
    }
    
    .mobile-header-dashboard {
        display: block;
    }
    
    .sidebar-modern {
        display: none !important;
    }
    
    .dashboard-header {
        display: none !important;
    }
    
    body {
        padding-bottom: 70px;
        overflow-x: hidden;
    }
    
    /* Hitung tinggi mobile-header-dashboard untuk spacing */
    .mobile-header-dashboard {
        height: auto;
        min-height: auto;
    }
    
    /* Dashboard main perlu padding-top untuk mobile header */
    /* Navbar: 56px (sudah di margin-top) + Mobile header: ~180px = perlu padding-top */
    .dashboard-main {
        margin-left: 0 !important;
        padding: 0 !important;
        padding-bottom: 80px !important;
        padding-top: 180px !important;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
        width: 100% !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        margin-top: 56px !important;
    }
    
    /* Pastikan konten pertama tidak ada margin tambahan */
    .dashboard-main > .forecast-card:first-of-type,
    .dashboard-main > .card-modern:first-of-type {
        margin-top: 0 !important;
    }
    
    /* Jika ada header dashboard di dalam, sembunyikan di mobile */
    .dashboard-main > header.dashboard-header {
        margin-top: 0 !important;
        padding-top: 0 !important;
        display: none !important;
    }
    
    .dashboard-container,
    .dashboard-layout {
        width: 100%;
        max-width: 100vw;
        overflow-x: hidden;
    }
    
    .dashboard-content {
        grid-template-columns: 1fr !important;
        gap: 1rem;
    }
    
    .card-modern {
        margin-bottom: 1rem;
    }
    
    .greeting {
        font-size: 1.1rem !important;
    }
    
    .header-left,
    .header-right {
        width: 100%;
    }
}

/* Three Cards Horizontal Layout */
.three-cards-horizontal {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    margin-top: 1.5rem;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    align-items: stretch;
}

.three-cards-horizontal .card-modern {
    width: 100%;
    max-width: 100%;
    margin-bottom: 0;
    margin-left: 0;
    margin-right: 0;
    min-height: auto;
    display: flex;
    flex-direction: column;
    height: auto;
}

.three-cards-horizontal .card-header-modern,
.three-cards-horizontal .weather-header {
    padding: 1.25rem 1.5rem;
}

.three-cards-horizontal .card-header-modern h3,
.three-cards-horizontal .weather-header h4 {
    font-size: 1.15rem;
    font-weight: 600;
}

.three-cards-horizontal .card-body-modern,
.three-cards-horizontal .weather-body {
    padding: 1.5rem;
    min-height: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.three-cards-horizontal .sun-card-content {
    gap: 1.25rem;
    margin-top: 0.75rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.three-cards-horizontal .sun-item {
    padding: 1.25rem;
    margin-bottom: 0;
    border-radius: 12px;
    background: rgba(59, 130, 246, 0.05);
    transition: all 0.3s ease;
}

.three-cards-horizontal .sun-item:hover {
    background: rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.three-cards-horizontal .sun-item:last-child {
    margin-bottom: 0;
}

.three-cards-horizontal .sun-item i {
    font-size: 2rem;
    color: #fbbf24;
}

.three-cards-horizontal .sun-label {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}

.three-cards-horizontal .sun-time {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-color);
}

.three-cards-horizontal .recommendations-card .card-body-modern {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.three-cards-horizontal .recommendation-item {
    padding: 1rem 1.25rem;
    margin-bottom: 0;
    border-radius: 12px;
    background: rgba(16, 185, 129, 0.05);
    border-left: 4px solid #10b981;
    transition: all 0.3s ease;
}

.three-cards-horizontal .recommendation-item:hover {
    background: rgba(16, 185, 129, 0.1);
    transform: translateX(4px);
}

.three-cards-horizontal .recommendation-item h6 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-color);
}

.three-cards-horizontal .recommendation-item p {
    font-size: 0.875rem;
    line-height: 1.5;
    margin: 0;
    color: var(--text-muted);
}

.three-cards-horizontal .chart-scroll-wrapper {
    min-height: auto;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.three-cards-horizontal .chart-scroll-wrapper canvas {
    min-height: auto;
    max-height: 300px;
    width: 100% !important;
    height: auto !important;
}

@media (max-width: 1200px) {
    .three-cards-horizontal {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .three-cards-horizontal .chart-card {
        grid-column: 1 / -1;
    }
    
    .three-cards-horizontal .card-modern {
        min-height: auto;
    }
    
    .three-cards-horizontal .card-body-modern,
    .three-cards-horizontal .weather-body {
        min-height: auto;
    }
}

@media (max-width: 768px) {
    .three-cards-horizontal {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .three-cards-horizontal .chart-card {
        grid-column: 1;
    }
    
    .three-cards-horizontal .card-modern {
        min-height: auto;
    }
    
    .three-cards-horizontal .card-body-modern,
    .three-cards-horizontal .weather-body {
        min-height: auto;
    }
}
</style>

<!-- Mobile Header -->
<div class="mobile-header-dashboard">
    <div class="mobile-header-dashboard-content">
        <h1>
            <i class="bi bi-cloud-sun"></i> 
            <?php echo $greeting_en; ?>, <span style="color: #fbbf24; font-weight: 700;"><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
        </h1>
        <div class="mobile-header-actions-dashboard">
            <button class="mobile-header-btn-dashboard" onclick="location.reload()" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>
    <form method="GET" class="mobile-search-dashboard">
        <input type="text" name="location" placeholder="Cari kota..." value="<?php echo htmlspecialchars($location); ?>">
        <button type="submit">
            <i class="bi bi-search"></i>
        </button>
    </form>
    <button class="mobile-location-btn-dashboard" onclick="getCurrentLocation()">
        <i class="bi bi-geo-alt-fill"></i> Gunakan Lokasi Saya
    </button>
</div>

<div class="dashboard-container" style="padding-top: 0; margin-top: 0;">
    <div class="dashboard-layout">
        <!-- Left Sidebar -->
        <aside class="sidebar-modern d-none d-md-block">
            <div class="sidebar-header">
                <i class="bi bi-cloud-sun fs-3"></i>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo base_url('dashboard.php'); ?>" class="nav-item active" title="Dashboard">
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
                <a href="<?php echo base_url('profile.php'); ?>" class="nav-item" title="Profile">
                    <i class="bi bi-person"></i>
                </a>
                <?php if (isAdmin()): ?>
                <a href="<?php echo base_url('admin/index.php'); ?>" class="nav-item" title="Admin">
                    <i class="bi bi-gear"></i>
                </a>
                <?php endif; ?>
                <a href="<?php echo base_url('auth/logout.php'); ?>" class="nav-item" title="Logout">
                    <i class="bi bi-power"></i>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main" style="margin-top: 56px; padding-top: 1rem;">
            <!-- Top Header -->
            <header class="dashboard-header" style="margin-top: 0; padding-top: 0;">
                <div class="header-left">
                    <h1 class="greeting">
                        <?php echo $greeting_en; ?>, <span style="color: #fbbf24; font-weight: 700;"><?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                        <?php if ($current_hour >= 6 && $current_hour < 12): ?>
                            <i class="bi bi-sun"></i>
                        <?php elseif ($current_hour >= 12 && $current_hour < 18): ?>
                            <i class="bi bi-sun-fill"></i>
                        <?php else: ?>
                            <i class="bi bi-moon-stars"></i>
                        <?php endif; ?>
                    </h1>
                    <p class="date-text"><?php echo date('l, d F Y'); ?></p>
                </div>
                <div class="header-right">
                    <div class="header-actions">
                        <button class="icon-btn" onclick="refreshWeather()" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <form method="GET" class="search-form">
                            <input type="text" class="search-input" name="location" placeholder="Cari kota..." value="<?php echo htmlspecialchars($location); ?>">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                        <button class="icon-btn" onclick="getCurrentLocation()" title="Gunakan Lokasi Saya">
                            <i class="bi bi-geo-alt-fill"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Weekly Forecast (Top) -->
            <div class="card-modern forecast-card mb-4">
                <div class="card-header-modern">
                    <h3>Prakiraan 7 Hari</h3>
                </div>
                <div class="card-body-modern">
                    <div class="forecast-scroll">
                        <?php if (!empty($daily_forecast) && is_array($daily_forecast)): ?>
                            <?php 
                            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            foreach ($daily_forecast as $forecast): 
                                if (!isset($forecast['dt']) || !isset($forecast['main']) || !isset($forecast['weather'][0])) {
                                    continue;
                                }
                                $day_name = $days[date('w', $forecast['dt'])];
                                $day_short = substr($day_name, 0, 3);
                                // Use temp_max if available, otherwise use temp (for daily forecast, prefer max temp)
                                $temp = round($forecast['main']['temp_max'] ?? $forecast['main']['temp'] ?? 0);
                                // Ensure temperature is reasonable (not too cold for Indonesia)
                                if ($temp < 15) {
                                    // If temp seems too low, check if it's actually temp_min
                                    if (isset($forecast['main']['temp_max']) && $forecast['main']['temp_max'] > $temp) {
                                        $temp = round($forecast['main']['temp_max']);
                                    } elseif (isset($forecast['main']['temp']) && $forecast['main']['temp'] > $temp) {
                                        $temp = round($forecast['main']['temp']);
                                    }
                                }
                                $icon = $forecast['weather'][0]['icon'] ?? '01d';
                                $is_today = date('Y-m-d', $forecast['dt']) === date('Y-m-d');
                            ?>
                            <div class="forecast-item <?php echo $is_today ? 'forecast-today' : ''; ?>">
                                <p class="forecast-day"><?php echo $day_short; ?></p>
                                <img src="https://openweathermap.org/img/wn/<?php echo $icon; ?>.png" alt="" class="forecast-icon">
                                <p class="forecast-temp"><?php echo $temp; ?>°</p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="forecast-error">
                                <p class="text-muted text-center mb-2">
                                    <i class="bi bi-exclamation-triangle"></i> Data tidak tersedia
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="dashboard-content">
                <!-- Left Column -->
                <div class="content-left">
                    <!-- Sunrise & Sunset -->
                    <div class="card-modern sunrise-card">
                        <div class="card-header-modern">
                            <h3>Matahari Terbit & Terbenam</h3>
                        </div>
                        <div class="card-body-modern">
                            <?php if ($weather_data && isset($weather_data['sys'])): 
                                $sunrise = date('g:i A', $weather_data['sys']['sunrise']);
                                $sunset = date('g:i A', $weather_data['sys']['sunset']);
                                $sunrise_time = date('H:i', $weather_data['sys']['sunrise']);
                                $sunset_time = date('H:i', $weather_data['sys']['sunset']);
                                $daylight_hours = round(($weather_data['sys']['sunset'] - $weather_data['sys']['sunrise']) / 3600);
                                $daylight_minutes = round((($weather_data['sys']['sunset'] - $weather_data['sys']['sunrise']) % 3600) / 60);
                            ?>
                            <p class="text-muted mb-3" style="font-size: 0.9rem;">
                                Hari ini, <?php echo htmlspecialchars($location); ?> dan sekitarnya
                            </p>
                            <div class="sunrise-item">
                                <div class="sunrise-icon">
                                    <i class="bi bi-sunrise"></i>
                                </div>
                                <div class="sunrise-info">
                                    <p class="sunrise-label">Terbit</p>
                                    <p class="sunrise-time"><?php echo $sunrise_time; ?></p>
                                </div>
                            </div>
                            <div class="sunrise-item">
                                <div class="sunrise-icon">
                                    <i class="bi bi-sunset"></i>
                                </div>
                                <div class="sunrise-info">
                                    <p class="sunrise-label">Terbenam</p>
                                    <p class="sunrise-time"><?php echo $sunset_time; ?></p>
                                </div>
                            </div>
                            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(0,0,0,0.1);">
                                <p class="mb-2" style="font-size: 0.9rem; color: var(--text-muted);">Durasi siang</p>
                                <p style="font-size: 1.1rem; font-weight: 600; color: var(--text-color);">
                                    <?php echo $daylight_hours; ?> Jam <?php echo $daylight_minutes; ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Today's Activities -->
                    <div class="card-modern activities-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <h3>Aktivitas Hari Ini</h3>
                            <a href="<?php echo base_url('activities/create.php'); ?>" class="btn-icon">
                                <i class="bi bi-plus-circle"></i>
                            </a>
                        </div>
                        <div class="card-body-modern">
                            <?php if (empty($today_activities)): ?>
                                <p class="text-muted text-center py-3">Tidak ada aktivitas hari ini</p>
                            <?php else: ?>
                                <div class="activities-list">
                                    <?php foreach ($today_activities as $act): ?>
                                    <div class="activity-item">
                                        <div class="activity-time">
                                            <?php echo $act['start_time']; ?> - <?php echo $act['end_time']; ?>
                                        </div>
                                        <div class="activity-details">
                                            <h6><?php echo htmlspecialchars($act['title']); ?></h6>
                                            <span class="activity-badge"><?php echo htmlspecialchars($act['category']); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Middle Column -->
                <div class="content-middle">
                    <!-- Moonrise & Moonset -->
                    <div class="card-modern sunrise-card">
                        <div class="card-header-modern">
                            <h3>Bulan Terbit & Tenggelam</h3>
                        </div>
                        <div class="card-body-modern">
                            <?php if ($weather_data && isset($weather_data['coord']) && isset($weather_data['sys'])): 
                                $lat = $weather_data['coord']['lat'];
                                $lon = $weather_data['coord']['lon'];
                                $sunrise_timestamp = $weather_data['sys']['sunrise'] ?? null;
                                $moon_times = calculateMoonTimes($lat, $lon, $sunrise_timestamp);
                                $moonrise_time = date('H:i', (int)$moon_times['moonrise']);
                                $moonset_time = date('H:i', (int)$moon_times['moonset']);
                                $moon_duration_seconds = (int)$moon_times['moonset'] - (int)$moon_times['moonrise'];
                                $moon_duration_hours = (int)round($moon_duration_seconds / 3600);
                                $moon_duration_minutes = (int)round(($moon_duration_seconds % 3600) / 60);
                            ?>
                            <p class="text-muted mb-3" style="font-size: 0.9rem;">
                                Hari ini, <?php echo htmlspecialchars($location); ?> dan sekitarnya
                            </p>
                            <div class="sunrise-item">
                                <div class="sunrise-icon">
                                    <i class="bi bi-moon-stars"></i>
                                </div>
                                <div class="sunrise-info">
                                    <p class="sunrise-label">Terbit</p>
                                    <p class="sunrise-time"><?php echo $moonrise_time; ?></p>
                                </div>
                            </div>
                            <div class="sunrise-item">
                                <div class="sunrise-icon">
                                    <i class="bi bi-moon"></i>
                                </div>
                                <div class="sunrise-info">
                                    <p class="sunrise-label">Tenggelam</p>
                                    <p class="sunrise-time"><?php echo $moonset_time; ?></p>
                                </div>
                            </div>
                            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(0,0,0,0.1);">
                                <p class="mb-2" style="font-size: 0.9rem; color: var(--text-muted);">Durasi malam</p>
                                <p style="font-size: 1.1rem; font-weight: 600; color: var(--text-color);">
                                    <?php echo $moon_duration_hours; ?> Jam <?php echo $moon_duration_minutes; ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Monthly Rainfall Chart -->
                    <div class="card-modern chart-card">
                        <div class="card-header-modern">
                            <h3>Hujan & Matahari Minggu Ini</h3>
                        </div>
                        <div class="card-body-modern">
                            <div class="chart-scroll-wrapper">
                                <canvas id="rainfallChart" style="max-height: 300px;"></canvas>
                            </div>
                            <?php if (!empty($daily_forecast)): 
                                $total_rain = 0;
                                $count = 0;
                                foreach ($daily_forecast as $f) {
                                    if (isset($f['rain']['3h'])) {
                                        $total_rain += $f['rain']['3h'];
                                        $count++;
                                    }
                                }
                                $avg_rain = $count > 0 ? round($total_rain / $count, 1) : 0;
                            ?>
                            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(0,0,0,0.1);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.9rem; color: var(--text-muted);">Rata-rata:</span>
                                    <span style="font-weight: 600; color: var(--text-color);"><?php echo $avg_rain; ?> mm</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span style="font-size: 0.9rem; color: var(--text-muted);">Pembaruan:</span>
                                    <span style="font-weight: 600; color: var(--text-color);"><?php echo date('H:i'); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="content-right" style="padding-left: 0; padding-right: 0;">
                    <!-- Weather Cards Scroll Container -->
                    <div class="weather-cards-scroll">
                        <div class="weather-cards-wrapper">
                            <!-- Current Weather (Main Card) -->
                            <div class="card-modern weather-main-card weather-card-slide">
                                <div class="weather-header">
                                    <div class="weather-location">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <span><?php echo htmlspecialchars($location); ?></span>
                                        <?php if ($lat && $lon): ?>
                                            <span class="badge">
                                                <i class="bi bi-crosshair"></i> Lokasi Saya
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="weather-date"><?php echo date('d F Y'); ?></p>
                                </div>
                                <div class="weather-body">
                                    <?php if ($weather_data && isset($weather_data['main'])): ?>
                                    <div class="weather-icon-large">
                                        <img src="https://openweathermap.org/img/wn/<?php echo $weather_data['weather'][0]['icon']; ?>@4x.png" alt="Weather">
                                    </div>
                                    <div class="weather-temp-large">
                                        <?php echo round($weather_data['main']['temp']); ?>°
                                    </div>
                                    <p class="weather-condition"><?php echo ucfirst($weather_data['weather'][0]['description']); ?></p>
                                    <div class="weather-details">
                                        <div class="weather-detail-item">
                                            <i class="bi bi-wind"></i>
                                            <span><?php echo round($weather_data['wind']['speed'] ?? 0); ?> km/h</span>
                                        </div>
                                        <div class="weather-detail-item">
                                            <i class="bi bi-droplet"></i>
                                            <span><?php echo $weather_data['main']['humidity']; ?>%</span>
                                        </div>
                                        <?php if (isset($weather_data['clouds']['all'])): ?>
                                        <div class="weather-detail-item">
                                            <i class="bi bi-cloud"></i>
                                            <span><?php echo $weather_data['clouds']['all']; ?>%</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($weather_data['main']['temp_min']) && isset($weather_data['main']['temp_max'])): ?>
                                    <div class="mt-3 pt-3" style="border-top: 1px solid rgba(255, 255, 255, 0.2);">
                                        <div class="d-flex justify-content-center gap-3" style="flex-wrap: wrap;">
                                            <div class="text-center">
                                                <small style="opacity: 0.8; font-size: 0.75rem; display: block; margin-bottom: 0.25rem;">Min</small>
                                                <strong style="font-size: 1.1rem; opacity: 0.95;"><?php echo round($weather_data['main']['temp_min']); ?>°</strong>
                                            </div>
                                            <div class="text-center">
                                                <small style="opacity: 0.8; font-size: 0.75rem; display: block; margin-bottom: 0.25rem;">Terasa</small>
                                                <strong style="font-size: 1.1rem; opacity: 0.95;"><?php echo round($weather_data['main']['feels_like']); ?>°</strong>
                                            </div>
                                            <div class="text-center">
                                                <small style="opacity: 0.8; font-size: 0.75rem; display: block; margin-bottom: 0.25rem;">Max</small>
                                                <strong style="font-size: 1.1rem; opacity: 0.95;"><?php echo round($weather_data['main']['temp_max']); ?>°</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Additional Weather Info Cards (for scrolling) -->
                            <?php if ($weather_data && isset($weather_data['main'])): ?>
                            <div class="card-modern weather-info-card weather-card-slide">
                                <div class="weather-header">
                                    <h4><i class="bi bi-info-circle"></i> Detail Cuaca</h4>
                                </div>
                                <div class="weather-body">
                                    <div class="weather-info-grid">
                                        <div class="weather-info-item">
                                            <i class="bi bi-thermometer-half"></i>
                                            <div>
                                                <p class="info-label">Terasa Seperti</p>
                                                <p class="info-value"><?php echo round($weather_data['main']['feels_like']); ?>°</p>
                                            </div>
                                        </div>
                                        <div class="weather-info-item">
                                            <i class="bi bi-arrow-down-up"></i>
                                            <div>
                                                <p class="info-label">Tekanan</p>
                                                <p class="info-value"><?php echo $weather_data['main']['pressure']; ?> hPa</p>
                                            </div>
                                        </div>
                                        <div class="weather-info-item">
                                            <i class="bi bi-eye"></i>
                                            <div>
                                                <p class="info-label">Visibilitas</p>
                                                <p class="info-value"><?php echo isset($weather_data['visibility']) ? round($weather_data['visibility'] / 1000, 1) : 'N/A'; ?> km</p>
                                            </div>
                                        </div>
                                        <div class="weather-info-item">
                                            <i class="bi bi-cloud"></i>
                                            <div>
                                                <p class="info-label">Awan</p>
                                                <p class="info-value"><?php echo $weather_data['clouds']['all'] ?? 0; ?>%</p>
                                                <?php if (isset($weather_data['clouds']['all'])): 
                                                    $cloud_percent = $weather_data['clouds']['all'];
                                                ?>
                                                <small class="text-muted" style="font-size: 0.7rem; display: block; margin-top: 0.25rem;">
                                                    <?php 
                                                    if ($cloud_percent < 25) {
                                                        echo 'Cerah';
                                                    } elseif ($cloud_percent < 50) {
                                                        echo 'Berawan Ringan';
                                                    } elseif ($cloud_percent < 75) {
                                                        echo 'Berawan';
                                                    } else {
                                                        echo 'Mendung';
                                                    }
                                                    ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Three Cards Horizontal Layout -->
                    <div class="three-cards-horizontal" style="margin-left: 0 !important; padding-left: 0 !important; width: 100% !important;">
                        <!-- Sun Card -->
                        <div class="card-modern weather-sun-card">
                            <div class="weather-header">
                                <h4><i class="bi bi-sun"></i> Matahari</h4>
                            </div>
                            <div class="weather-body">
                                <?php if (isset($weather_data['sys'])): ?>
                                    <?php 
                                        $sunrise = date('H:i', $weather_data['sys']['sunrise']);
                                        $sunset = date('H:i', $weather_data['sys']['sunset']);
                                    ?>
                                    <div class="sun-card-content">
                                        <div class="sun-item">
                                            <i class="bi bi-sunrise"></i>
                                            <div>
                                                <p class="sun-label">Terbit</p>
                                                <p class="sun-time"><?php echo $sunrise; ?></p>
                                            </div>
                                        </div>
                                        <div class="sun-item">
                                            <i class="bi bi-sunset"></i>
                                            <div>
                                                <p class="sun-label">Terbenam</p>
                                                <p class="sun-time"><?php echo $sunset; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="sun-card-content">
                                        <div class="sun-item">
                                            <i class="bi bi-sunrise"></i>
                                            <div>
                                                <p class="sun-label">Terbit</p>
                                                <p class="sun-time">--:--</p>
                                            </div>
                                        </div>
                                        <div class="sun-item">
                                            <i class="bi bi-sunset"></i>
                                            <div>
                                                <p class="sun-label">Terbenam</p>
                                                <p class="sun-time">--:--</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <div class="card-modern recommendations-card">
                            <div class="card-header-modern">
                                <h3>Rekomendasi Aktivitas</h3>
                            </div>
                            <div class="card-body-modern">
                                <?php if (!empty($recommendations)): ?>
                                    <?php foreach ($recommendations as $rec): ?>
                                    <div class="recommendation-item">
                                        <h6><?php echo htmlspecialchars($rec['activity']); ?></h6>
                                        <p class="text-muted small"><?php echo htmlspecialchars($rec['reason']); ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="recommendation-item">
                                        <h6>Menunggu data cuaca...</h6>
                                        <p class="text-muted small">Rekomendasi akan muncul setelah data cuaca tersedia</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="card-modern chart-card">
                            <div class="card-header-modern">
                                <h3>Tren Suhu (7 Hari)</h3>
                            </div>
                            <div class="card-body-modern">
                                <div class="chart-scroll-wrapper">
                                    <canvas id="temperatureChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Mobile Menu -->
<div class="mobile-menu-dashboard">
    <div class="mobile-menu-items">
        <a href="<?php echo base_url('dashboard.php'); ?>" class="mobile-menu-item active">
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
      
        <a href="<?php echo base_url('profile.php'); ?>" class="mobile-menu-item">
            <i class="bi bi-person-fill"></i>
            <span>Profile</span>
        </a>
    </div>
</div>

<script>
// Function to refresh weather data
function refreshWeather() {
    const url = new URL(window.location.href);
    url.searchParams.set('refresh', '1');
    // Preserve lat/lon if exists
    if (url.searchParams.has('lat') && url.searchParams.has('lon')) {
        // Keep coordinates
    } else if (url.searchParams.has('location')) {
        // Keep location
    }
    window.location.href = url.toString();
}

// Temperature Chart
document.addEventListener('DOMContentLoaded', function() {
    const tempCtx = document.getElementById('temperatureChart');
    if (tempCtx) {
        const tempData = <?php echo json_encode($temp_trend ?? []); ?>;
        
        if (tempData && tempData.length > 0) {
            try {
                new Chart(tempCtx, {
                    type: 'line',
                    data: {
                        labels: tempData.map(d => {
                            const date = new Date(d.recorded_at);
                            return date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
                        }),
                        datasets: [{
                            label: 'Suhu (°C)',
                            data: tempData.map(d => parseFloat(d.temperature)),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgb(59, 130, 246)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.5,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function(context) {
                                        return 'Suhu: ' + context.parsed.y.toFixed(1) + '°C';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    },
                                    padding: 8
                                }
                            },
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value.toFixed(0) + '°';
                                    },
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    },
                                    padding: 8
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Chart error:', error);
                tempCtx.parentElement.innerHTML = '<p class="text-muted text-center py-4">Gagal memuat grafik. Error: ' + error.message + '</p>';
            }
        } else {
            tempCtx.parentElement.innerHTML = '<div class="text-center py-4"><p class="text-muted mb-2">Data tren suhu belum tersedia</p><small class="text-muted">Data akan muncul setelah beberapa hari penggunaan</small></div>';
        }
    } else {
        console.error('Canvas element not found');
    }
    
    // Rainfall Chart
    const rainfallCtx = document.getElementById('rainfallChart');
    if (rainfallCtx) {
        const forecastData = <?php echo json_encode($daily_forecast ?? []); ?>;
        
        if (forecastData && forecastData.length > 0) {
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const labels = forecastData.map(f => {
                const date = new Date(f.dt * 1000);
                return days[date.getDay()].substring(0, 3);
            });
            
            const rainData = forecastData.map(f => {
                return f.rain && f.rain['3h'] ? parseFloat(f.rain['3h']) : 0;
            });
            
            // Estimate sunlight hours (simplified: 12 - rain hours)
            const sunData = forecastData.map(f => {
                const rain = f.rain && f.rain['3h'] ? parseFloat(f.rain['3h']) : 0;
                return Math.max(0, 12 - (rain / 10)); // Simplified calculation
            });
            
            try {
                new Chart(rainfallCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Hujan (mm)',
                                data: rainData,
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                borderColor: 'rgb(59, 130, 246)',
                                borderWidth: 1
                            },
                            {
                                label: 'Matahari (jam)',
                                data: sunData,
                                backgroundColor: 'rgba(251, 191, 36, 0.7)',
                                borderColor: 'rgb(251, 191, 36)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 2,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Rainfall chart error:', error);
                rainfallCtx.parentElement.innerHTML = '<p class="text-muted text-center py-4">Gagal memuat grafik</p>';
            }
        } else {
            rainfallCtx.parentElement.innerHTML = '<div class="text-center py-4"><p class="text-muted mb-2">Data belum tersedia</p></div>';
        }
    }
});

// Get current location (manual trigger) - override untuk dashboard
function getCurrentLocation() {
    if (navigator.geolocation) {
        // Show loading indicator
        const btn = event?.target?.closest('.icon-btn') || event?.target?.closest('button') || event?.target?.closest('a');
        let originalHTML = '';
        if (btn) {
            originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            if (btn.tagName === 'BUTTON' || btn.tagName === 'A') {
                btn.disabled = true;
            }
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                window.location.href = '<?php echo base_url("dashboard.php"); ?>?lat=' + lat + '&lon=' + lon;
            },
            function(error) {
                if (btn) {
                    btn.innerHTML = originalHTML;
                    if (btn.tagName === 'BUTTON' || btn.tagName === 'A') {
                        btn.disabled = false;
                    }
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
                
                // Show nice error message
                if (confirm(errorMsg + '\n\nIngin mencari lokasi secara manual?')) {
                    const searchInput = document.querySelector('.search-input');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        alert('Browser Anda tidak mendukung geolocation. Silakan cari lokasi secara manual.');
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.focus();
        }
    }
}


// Notification functions are now in assets/js/notifications.js
</script>

<?php include 'includes/footer.php'; ?>
