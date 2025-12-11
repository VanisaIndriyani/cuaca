<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Services/ApiClientWeather.php';
require_once __DIR__ . '/../app/Models/WeatherData.php';

requireLogin();

$page_title = 'Cuaca';
$location = $_GET['location'] ?? $_SESSION['user_location'] ?? 'Jakarta';
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

$apiClient = new ApiClientWeather();
$weatherModel = new WeatherData($db);

// Prioritize location from user activities (most accurate)
require_once __DIR__ . '/../app/Models/Activity.php';
$activityModel = new Activity($db);
$user_id = $_SESSION['user_id'];
$activity_location = $activityModel->getMostUsedLocation($user_id);

// Fetch weather data
if ($lat && $lon) {
    $weather_data = $apiClient->fetchWeatherByCoords($lat, $lon);
    if ($weather_data) {
        // Prioritize activity location if it's a named location (not coordinate)
        if ($activity_location && !preg_match('/^-?\d+\.?\d*,\s*-?\d+\.?\d*$/', $activity_location)) {
            // Use activity location (user's manual input is more accurate)
            $location = $activity_location;
        } else {
            // Get detailed location name (desa/kecamatan) from reverse geocoding
            $detailed_location = $apiClient->formatLocationName($weather_data, $lat, $lon);
            $location = $detailed_location ?: ($weather_data['name'] ?? $location);
        }
    }
} else {
    // If no coordinates, prioritize activity location
    if ($activity_location && !preg_match('/^-?\d+\.?\d*,\s*-?\d+\.?\d*$/', $activity_location)) {
        $location = $activity_location;
    }
    $weather_data = $apiClient->fetchCurrentWeather($location);
}

// Save to session
$_SESSION['user_location'] = $location;

$forecast_data = $apiClient->fetchForecast($location, 5);

// Get historical data
$historical_data = $weatherModel->getAverageByWeek($location);

// Format forecast data
$daily_forecast = [];
if ($forecast_data && isset($forecast_data['list'])) {
    foreach ($forecast_data['list'] as $item) {
        $date = date('Y-m-d', $item['dt']);
        if (!isset($daily_forecast[$date])) {
            $daily_forecast[$date] = $item;
        }
    }
    $daily_forecast = array_slice($daily_forecast, 0, 5);
}

include '../includes/header.php';
?>

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

.mobile-menu-item.active,
.mobile-menu-item:hover {
    color: var(--primary-color);
    background: rgba(59, 130, 246, 0.1);
}

/* Mobile Header */
.mobile-header {
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

.mobile-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
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
    position: relative;
}

.mobile-header-btn:hover {
    background: rgba(255,255,255,0.3);
}

.mobile-header h1 {
    font-size: 1.25rem;
    margin: 0;
    font-weight: 600;
}

.mobile-search {
    display: flex;
    gap: 0.5rem;
    width: 100%;
}

.mobile-search input {
    flex: 1;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.mobile-search button {
    border: none;
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    transition: all 0.3s;
}

.mobile-search button:hover {
    background: rgba(255,255,255,0.3);
}

.mobile-location-btn {
    width: 100%;
    margin-top: 0.5rem;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white;
    border-radius: 8px;
    padding: 0.5rem;
    font-size: 0.9rem;
}

/* Weather Cards Mobile */
.weather-card-mobile {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 1.75rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.weather-card-mobile:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.weather-main-mobile {
    text-align: center;
    padding: 1rem 0;
}

.weather-icon-mobile {
    width: 140px;
    height: 140px;
    margin: 0 auto;
    filter: drop-shadow(0 4px 12px rgba(59, 130, 246, 0.3));
    transition: all 0.3s ease;
}

.weather-icon-mobile:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 6px 16px rgba(59, 130, 246, 0.4));
}

.weather-temp-mobile {
    font-size: 4.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
    line-height: 1;
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.weather-condition-mobile {
    font-size: 1.3rem;
    color: var(--text-color);
    margin-bottom: 1.5rem;
    font-weight: 600;
    text-transform: capitalize;
}

.weather-details-mobile {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}

.weather-detail-item-mobile {
    text-align: center;
    padding: 1.25rem;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(96, 165, 250, 0.08) 100%);
    border-radius: 14px;
    border: 2px solid rgba(59, 130, 246, 0.1);
    transition: all 0.3s ease;
}

.weather-detail-item-mobile:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(96, 165, 250, 0.15) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.2);
}

.weather-detail-item-mobile i {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.weather-detail-item-mobile .label {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.weather-detail-item-mobile .value {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-color);
}

/* Forecast Mobile */
.forecast-scroll-mobile {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding: 1rem 0;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
}

.forecast-scroll-mobile::-webkit-scrollbar {
    height: 6px;
}

.forecast-scroll-mobile::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.05);
    border-radius: 3px;
}

.forecast-scroll-mobile::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}

.forecast-scroll-mobile::-webkit-scrollbar-thumb:hover {
    background: var(--secondary-color);
}

.forecast-item-mobile {
    min-width: 110px;
    text-align: center;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(96, 165, 250, 0.08) 100%);
    border-radius: 14px;
    padding: 1.25rem 0.875rem;
    border: 2px solid rgba(59, 130, 246, 0.1);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.forecast-item-mobile:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(96, 165, 250, 0.15) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.2);
}

.forecast-day-mobile {
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

.forecast-date-mobile {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}

.forecast-icon-mobile {
    width: 60px;
    height: 60px;
    margin: 0.75rem auto;
    filter: drop-shadow(0 2px 6px rgba(59, 130, 246, 0.2));
    transition: all 0.3s ease;
}

.forecast-item-mobile:hover .forecast-icon-mobile {
    transform: scale(1.1);
}

.forecast-temp-mobile {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0.5rem 0;
    color: var(--text-color);
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.forecast-desc-mobile {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* Chart Mobile */
.chart-mobile {
    min-height: 250px;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar-modern {
        display: none !important;
    }
    
    .mobile-header {
        display: block;
    }
    
    .mobile-menu {
        display: block;
    }
    
    .weather-main-content {
        width: 100% !important;
        padding: 0 !important;
        margin-left: 0 !important;
        padding-top: 180px !important; /* Navbar 56px + Mobile header ~124px */
    }
    
    .container-fluid {
        padding: 0;
        margin-top: 0 !important;
        overflow-x: hidden;
    }
    
    .p-4 {
        padding: 1rem !important;
        padding-top: 0 !important;
    }
    
    .d-flex.justify-content-between {
        display: none !important;
    }
    
    .card .row {
        flex-direction: column;
    }
    
    .card .col-md-4,
    .card .col-md-8,
    .card .col-md-6 {
        width: 100%;
        max-width: 100%;
    }
    
    body {
        padding-bottom: 70px;
        overflow-x: hidden;
    }
    
    /* Pastikan konten bisa di-scroll */
    .weather-main-content {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 20px !important;
    }
    
    /* Pastikan weather card mobile tidak tertutup */
    .weather-card-mobile {
        margin-top: 0 !important;
        position: relative;
        z-index: 1;
    }
    
    /* Pastikan forecast scroll mobile bisa di-scroll */
    .forecast-scroll-mobile {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 10px;
    }
    
    /* Pastikan semua konten terlihat */
    .p-4 {
        padding-bottom: 80px !important; /* Space untuk bottom nav */
    }
    
    .weather-card-mobile h3,
    .weather-card-mobile h4 {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .weather-card-mobile h3 i,
    .weather-card-mobile h4 i {
        color: var(--primary-color);
    }
    
    .weather-details-mobile {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .forecast-scroll-mobile {
        padding: 0.5rem 0;
    }
}

@media (min-width: 769px) {
    .mobile-header,
    .mobile-menu {
        display: none !important;
    }
    
    /* Desktop Layout */
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
        padding: 0;
    }
    
    .row {
        margin: 0;
        display: flex;
    }
    
    .weather-main-content {
        flex: 1;
        margin-left: 80px;
        margin-top: 56px;
        width: calc(100% - 80px);
        max-width: calc(100% - 80px);
        padding-left: 2rem;
        padding-right: 2rem;
        padding-top: 0;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    
    .p-4 {
        padding: 2rem !important;
        padding-top: 2.5rem !important;
    }
    
    .d-flex.justify-content-between.align-items-center {
        margin-top: 0;
        padding-top: 0;
    }
    
    .card-modern {
        border-radius: 20px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .card-modern:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .card-header-modern {
        background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        color: white;
        padding: 1.5rem 1.75rem;
        border-bottom: none;
        position: relative;
        overflow: hidden;
    }
    
    .card-header-modern::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    
    .card-header-modern h3 {
        position: relative;
        z-index: 1;
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
    }
    
    .card-body-modern {
        padding: 1.75rem;
        background: var(--card-bg);
    }
    
    /* Styling untuk h3 lokasi di card-body */
    .card-body-modern h3 {
        color: #fbbf24 !important;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .card .card {
        transition: all 0.3s ease;
    }
    
    .card .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.2);
        border-color: rgba(59, 130, 246, 0.3);
    }
}

@media (min-width: 992px) {
    .weather-main-content {
        padding-left: 2.5rem;
        padding-right: 2.5rem;
    }
}

@media (min-width: 1200px) {
    .container-fluid {
        max-width: 1600px;
        margin: 0 auto;
    }
    
    .weather-main-content {
        padding-left: 3rem;
        padding-right: 3rem;
    }
}
</style>

<!-- Mobile Header -->
<div class="mobile-header">
    <div class="mobile-header-content">
        <h1><i class="bi bi-cloud-sun"></i> Cuaca</h1>
    </div>
    <form method="GET" class="mobile-search">
        <input type="text" name="location" placeholder="Cari kota..." value="<?php echo htmlspecialchars($location); ?>">
        <button type="submit">
            <i class="bi bi-search"></i>
        </button>
    </form>
    <button class="mobile-location-btn" onclick="getCurrentLocation()">
        <i class="bi bi-geo-alt-fill"></i> Gunakan Lokasi Saya
    </button>
</div>

<div class="container-fluid">
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
                <a href="<?php echo base_url('weather/index.php'); ?>" class="nav-item active" title="Cuaca">
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

        <div class="col-md-10 weather-main-content">
            <div class="p-4" style="padding-top: 2.5rem !important;">
                <!-- Desktop Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 d-none d-md-flex" style="margin-top: 0; padding-top: 0;">
                    <h2 style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-cloud-sun" style="color: var(--primary-color);"></i> Informasi Cuaca
                    </h2>
                    <div class="d-flex gap-2">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control" name="location" placeholder="Cari kota..." value="<?php echo htmlspecialchars($location); ?>" style="max-width: 200px; border-radius: 8px 0 0 8px;">
                            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); border: none; border-radius: 0 8px 8px 0; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                        <button class="btn btn-outline-primary" onclick="getCurrentLocation()" style="border-radius: 8px; border-color: var(--primary-color); color: var(--primary-color); font-weight: 600; transition: all 0.3s;">
                            <i class="bi bi-geo-alt"></i> Lokasi Saya
                        </button>
                    </div>
                </div>

                <?php if ($weather_data && isset($weather_data['main'])): ?>
                <!-- Current Weather - Mobile -->
                <div class="weather-card-mobile d-md-none">
                    <div class="weather-main-mobile">
                        <h3 class="mb-3"><?php echo htmlspecialchars($location); ?></h3>
                        <p class="text-muted mb-3"><?php echo date('l, d F Y'); ?></p>
                        <img src="https://openweathermap.org/img/wn/<?php echo $weather_data['weather'][0]['icon']; ?>@4x.png" alt="Weather Icon" class="weather-icon-mobile">
                        <div class="weather-temp-mobile"><?php echo round($weather_data['main']['temp']); ?>°</div>
                        <div class="weather-condition-mobile"><?php echo ucfirst($weather_data['weather'][0]['description']); ?></div>
                        
                        <div class="weather-details-mobile">
                            <div class="weather-detail-item-mobile">
                                <i class="bi bi-thermometer-half"></i>
                                <div class="label">Terasa seperti</div>
                                <div class="value"><?php echo round($weather_data['main']['feels_like']); ?>°C</div>
                            </div>
                            <div class="weather-detail-item-mobile">
                                <i class="bi bi-droplet"></i>
                                <div class="label">Kelembapan</div>
                                <div class="value"><?php echo $weather_data['main']['humidity']; ?>%</div>
                            </div>
                            <div class="weather-detail-item-mobile">
                                <i class="bi bi-wind"></i>
                                <div class="label">Angin</div>
                                <div class="value"><?php echo round($weather_data['wind']['speed'] ?? 0); ?> km/h</div>
                            </div>
                            <div class="weather-detail-item-mobile">
                                <i class="bi bi-speedometer2"></i>
                                <div class="label">Tekanan</div>
                                <div class="value"><?php echo $weather_data['main']['pressure']; ?> hPa</div>
                            </div>
                            <?php if (isset($weather_data['clouds']['all'])): ?>
                            <div class="weather-detail-item-mobile">
                                <i class="bi bi-cloud"></i>
                                <div class="label">Awan</div>
                                <div class="value"><?php echo $weather_data['clouds']['all']; ?>%</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Awan & Suhu Detail - Mobile -->
                        <?php if (isset($weather_data['clouds']['all']) || isset($weather_data['main'])): ?>
                        <div class="mt-4 pt-4" style="border-top: 2px solid rgba(59, 130, 246, 0.1);">
                            <h5 class="mb-3" style="color: var(--text-color); font-weight: 600;">
                                <i class="bi bi-cloud-sun" style="color: var(--primary-color);"></i> Detail Awan & Suhu
                            </h5>
                            
                            <?php if (isset($weather_data['clouds']['all'])): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span style="color: var(--text-muted); font-size: 0.9rem;">Tutupan Awan</span>
                                    <strong style="color: var(--primary-color);"><?php echo $weather_data['clouds']['all']; ?>%</strong>
                                </div>
                                <div class="progress" style="height: 12px; border-radius: 6px; background: rgba(59, 130, 246, 0.1);">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $weather_data['clouds']['all']; ?>%; background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); border-radius: 6px;" aria-valuenow="<?php echo $weather_data['clouds']['all']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    $cloud_percent = $weather_data['clouds']['all'];
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
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($weather_data['main']['temp_min']) && isset($weather_data['main']['temp_max'])): ?>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="text-center p-2" style="background: rgba(59, 130, 246, 0.1); border-radius: 8px;">
                                        <small class="text-muted d-block">Min</small>
                                        <strong style="color: #60a5fa; font-size: 1.1rem;"><?php echo round($weather_data['main']['temp_min']); ?>°</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center p-2" style="background: rgba(59, 130, 246, 0.1); border-radius: 8px;">
                                        <small class="text-muted d-block">Saat Ini</small>
                                        <strong style="color: #3b82f6; font-size: 1.1rem;"><?php echo round($weather_data['main']['temp']); ?>°</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center p-2" style="background: rgba(251, 191, 36, 0.1); border-radius: 8px;">
                                        <small class="text-muted d-block">Max</small>
                                        <strong style="color: #f59e0b; font-size: 1.1rem;"><?php echo round($weather_data['main']['temp_max']); ?>°</strong>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Current Weather - Desktop -->
                <div class="card card-modern mb-4 d-none d-md-block">
                    <div class="card-header-modern">
                        <h3><i class="bi bi-cloud-sun"></i> Cuaca Saat Ini</h3>
                    </div>
                    <div class="card-body-modern">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <img src="https://openweathermap.org/img/wn/<?php echo $weather_data['weather'][0]['icon']; ?>@4x.png" alt="Weather Icon">
                                <h1 class="display-4"><?php echo round($weather_data['main']['temp']); ?>°C</h1>
                                <p class="lead"><?php echo ucfirst($weather_data['weather'][0]['description']); ?></p>
                            </div>
                            <div class="col-md-8">
                                <h3 style="color: #fbbf24 !important; font-weight: 700;"><?php echo htmlspecialchars($location); ?></h3>
                                <p class="text-muted"><?php echo date('l, d F Y'); ?></p>
                                <div class="row mt-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-thermometer-half fs-4 me-3"></i>
                                            <div>
                                                <small class="text-muted">Terasa seperti</small>
                                                <p class="mb-0"><strong><?php echo round($weather_data['main']['feels_like']); ?>°C</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-droplet fs-4 me-3"></i>
                                            <div>
                                                <small class="text-muted">Kelembapan</small>
                                                <p class="mb-0"><strong><?php echo $weather_data['main']['humidity']; ?>%</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-wind fs-4 me-3"></i>
                                            <div>
                                                <small class="text-muted">Angin</small>
                                                <p class="mb-0"><strong><?php echo round($weather_data['wind']['speed'] ?? 0); ?> km/h</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-speedometer2 fs-4 me-3"></i>
                                            <div>
                                                <small class="text-muted">Tekanan</small>
                                                <p class="mb-0"><strong><?php echo $weather_data['main']['pressure']; ?> hPa</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (isset($weather_data['visibility'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-eye fs-4 me-3"></i>
                                            <div>
                                                <small class="text-muted">Visibilitas</small>
                                                <p class="mb-0"><strong><?php echo round($weather_data['visibility'] / 1000, 1); ?> km</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (isset($weather_data['clouds']['all'])): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-cloud fs-4 me-3"></i>
                                            <div>
                                                <small class="text-muted">Awan</small>
                                                <p class="mb-0"><strong><?php echo $weather_data['clouds']['all']; ?>%</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Awan & Suhu Detail Card - Desktop -->
                <div class="card card-modern mb-4 d-none d-md-block">
                    <div class="card-header-modern">
                        <h3><i class="bi bi-cloud-sun"></i> Informasi Awan & Suhu</h3>
                    </div>
                    <div class="card-body-modern">
                        <div class="row g-4">
                            <!-- Cloud Cover Card -->
                            <div class="col-md-6">
                                <div class="card" style="border-radius: 16px; border: 2px solid rgba(59, 130, 246, 0.2); background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(96, 165, 250, 0.08) 100%); transition: all 0.3s ease;">
                                    <div class="card-body text-center" style="padding: 2rem;">
                                        <i class="bi bi-cloud fs-1 mb-3" style="color: #3b82f6;"></i>
                                        <h4 class="mb-2">Tutupan Awan</h4>
                                        <?php if (isset($weather_data['clouds']['all'])): ?>
                                        <div class="mb-3">
                                            <div class="progress" style="height: 20px; border-radius: 10px; background: rgba(59, 130, 246, 0.1);">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $weather_data['clouds']['all']; ?>%; background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); border-radius: 10px;" aria-valuenow="<?php echo $weather_data['clouds']['all']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $weather_data['clouds']['all']; ?>%
                                                </div>
                                            </div>
                                        </div>
                                        <p class="mb-0">
                                            <?php 
                                            $cloud_percent = $weather_data['clouds']['all'];
                                            if ($cloud_percent < 25) {
                                                echo '<span class="badge bg-success">Cerah</span>';
                                            } elseif ($cloud_percent < 50) {
                                                echo '<span class="badge bg-info">Berawan Ringan</span>';
                                            } elseif ($cloud_percent < 75) {
                                                echo '<span class="badge bg-warning">Berawan</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Mendung</span>';
                                            }
                                            ?>
                                        </p>
                                        <?php else: ?>
                                        <p class="text-muted">Data tidak tersedia</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Temperature Detail Card -->
                            <div class="col-md-6">
                                <div class="card" style="border-radius: 16px; border: 2px solid rgba(251, 191, 36, 0.2); background: linear-gradient(135deg, rgba(251, 191, 36, 0.08) 0%, rgba(245, 158, 11, 0.08) 100%); transition: all 0.3s ease;">
                                    <div class="card-body text-center" style="padding: 2rem;">
                                        <i class="bi bi-thermometer-half fs-1 mb-3" style="color: #fbbf24;"></i>
                                        <h4 class="mb-3">Detail Suhu</h4>
                                        <?php if (isset($weather_data['main'])): ?>
                                        <div class="row g-3 text-start">
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center p-2" style="background: rgba(255,255,255,0.5); border-radius: 8px;">
                                                    <span class="text-muted">Suhu Saat Ini</span>
                                                    <strong style="color: #3b82f6; font-size: 1.25rem;"><?php echo round($weather_data['main']['temp']); ?>°C</strong>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center p-2" style="background: rgba(255,255,255,0.5); border-radius: 8px;">
                                                    <span class="text-muted">Terasa Seperti</span>
                                                    <strong style="color: #f59e0b; font-size: 1.25rem;"><?php echo round($weather_data['main']['feels_like']); ?>°C</strong>
                                                </div>
                                            </div>
                                            <?php if (isset($weather_data['main']['temp_min']) && isset($weather_data['main']['temp_max'])): ?>
                                            <div class="col-6">
                                                <div class="text-center p-2" style="background: rgba(255,255,255,0.5); border-radius: 8px;">
                                                    <small class="text-muted d-block">Min</small>
                                                    <strong style="color: #60a5fa;"><?php echo round($weather_data['main']['temp_min']); ?>°</strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center p-2" style="background: rgba(255,255,255,0.5); border-radius: 8px;">
                                                    <small class="text-muted d-block">Max</small>
                                                    <strong style="color: #ef4444;"><?php echo round($weather_data['main']['temp_max']); ?>°</strong>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted">Data tidak tersedia</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Forecast - Mobile -->
                <?php if (!empty($daily_forecast)): ?>
                <div class="weather-card-mobile d-md-none">
                    <h4 class="mb-3"><i class="bi bi-calendar3"></i> Prakiraan 5 Hari</h4>
                    <div class="forecast-scroll-mobile">
                        <?php 
                        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                        foreach ($daily_forecast as $forecast): 
                            $day_name = $days[date('w', $forecast['dt'])];
                        ?>
                        <div class="forecast-item-mobile">
                            <div class="forecast-day-mobile"><?php echo $day_name; ?></div>
                            <div class="forecast-date-mobile"><?php echo date('d M', $forecast['dt']); ?></div>
                            <img src="https://openweathermap.org/img/wn/<?php echo $forecast['weather'][0]['icon']; ?>.png" alt="" class="forecast-icon-mobile">
                            <div class="forecast-temp-mobile"><?php echo round($forecast['main']['temp']); ?>°</div>
                            <div class="forecast-desc-mobile"><?php echo ucfirst($forecast['weather'][0]['description']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Forecast - Desktop -->
                <?php if ($forecast_data && isset($forecast_data['list'])): ?>
                <div class="card card-modern mb-4 d-none d-md-block">
                    <div class="card-header-modern">
                        <h3><i class="bi bi-calendar3"></i> Prakiraan 5 Hari</h3>
                    </div>
                    <div class="card-body-modern">
                        <div class="row g-3">
                            <?php foreach ($daily_forecast as $forecast): ?>
                            <div class="col-6 col-md-2">
                                <div class="card text-center" style="border-radius: 16px; border: 2px solid rgba(59, 130, 246, 0.1); transition: all 0.3s ease; background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(96, 165, 250, 0.05) 100%);">
                                    <div class="card-body" style="padding: 1.25rem;">
                                        <p class="mb-1"><strong><?php echo date('D', $forecast['dt']); ?></strong></p>
                                        <p class="mb-1"><small><?php echo date('d M', $forecast['dt']); ?></small></p>
                                        <img src="https://openweathermap.org/img/wn/<?php echo $forecast['weather'][0]['icon']; ?>.png" alt="">
                                        <p class="mb-0"><strong><?php echo round($forecast['main']['temp']); ?>°C</strong></p>
                                        <small class="text-muted"><?php echo ucfirst($forecast['weather'][0]['description']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Historical Chart -->
                <?php if (!empty($historical_data)): ?>
                <div class="weather-card-mobile d-md-none">
                    <h4 class="mb-3"><i class="bi bi-graph-up"></i> Tren Suhu & Kelembapan</h4>
                    <div class="chart-mobile">
                        <canvas id="weatherChartMobile"></canvas>
                    </div>
                </div>
                
                <div class="card card-modern d-none d-md-block">
                    <div class="card-header-modern">
                        <h3><i class="bi bi-graph-up"></i> Tren Suhu & Kelembapan (7 Hari Terakhir)</h3>
                    </div>
                    <div class="card-body-modern">
                        <canvas id="weatherChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
        <a href="<?php echo base_url('weather/index.php'); ?>" class="mobile-menu-item active">
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
function getCurrentLocation() {
    if (navigator.geolocation) {
        const btn = event?.target?.closest('button') || event?.target?.closest('a') || event?.target;
        let originalHTML = '';
        if (btn) {
            originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memuat...';
            if (btn.tagName === 'BUTTON' || btn.tagName === 'A') {
                btn.disabled = true;
            }
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                window.location.href = '<?php echo base_url("weather/index.php"); ?>?lat=' + lat + '&lon=' + lon;
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
                        errorMsg += 'Akses lokasi ditolak.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMsg += 'Informasi lokasi tidak tersedia.';
                        break;
                    case error.TIMEOUT:
                        errorMsg += 'Waktu permintaan habis.';
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
        alert('Browser tidak mendukung geolocation.');
    }
}

<?php if (!empty($historical_data)): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Chart
    const weatherCtxMobile = document.getElementById('weatherChartMobile');
    if (weatherCtxMobile) {
        const historicalData = <?php echo json_encode($historical_data); ?>;
        new Chart(weatherCtxMobile, {
            type: 'line',
            data: {
                labels: historicalData.map(d => new Date(d.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })),
                datasets: [
                    {
                        label: 'Suhu (°C)',
                        data: historicalData.map(d => parseFloat(d.avg_temp)),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Kelembapan (%)',
                        data: historicalData.map(d => parseFloat(d.avg_humidity)),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                }
            }
        });
    }
    
    // Desktop Chart
    const weatherCtx = document.getElementById('weatherChart');
    if (weatherCtx) {
        const historicalData = <?php echo json_encode($historical_data); ?>;
        new Chart(weatherCtx, {
            type: 'line',
            data: {
                labels: historicalData.map(d => new Date(d.date).toLocaleDateString('id-ID')),
                datasets: [
                    {
                        label: 'Suhu Rata-rata (°C)',
                        data: historicalData.map(d => parseFloat(d.avg_temp)),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: 'Kelembapan Rata-rata (%)',
                        data: historicalData.map(d => parseFloat(d.avg_humidity)),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                }
            }
        });
    }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
