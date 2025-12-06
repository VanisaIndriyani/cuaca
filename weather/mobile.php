<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Services/ApiClientWeather.php';
require_once __DIR__ . '/../app/Models/WeatherData.php';

requireLogin();

$page_title = 'Cuaca - Lokasi Saya';
$location = $_GET['location'] ?? $_SESSION['user_location'] ?? 'Jakarta';
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

$apiClient = new ApiClientWeather();
$weatherModel = new WeatherData($db);

// Fetch weather data
if ($lat && $lon) {
    $weather_data = $apiClient->fetchWeatherByCoords($lat, $lon);
    if ($weather_data) {
        $location = $weather_data['name'] ?? $location;
    }
} else {
    $weather_data = $apiClient->fetchCurrentWeather($location);
}

$forecast_data = $apiClient->fetchForecast($location, 10);

// Process hourly forecast (next 6 hours)
$hourly_forecast = [];
if ($forecast_data && isset($forecast_data['list'])) {
    $now = time();
    $count = 0;
    foreach ($forecast_data['list'] as $item) {
        if ($item['dt'] >= $now && $count < 6) {
            $hourly_forecast[] = $item;
            $count++;
        }
    }
}

// Process 10-day forecast
$daily_forecast = [];
if ($forecast_data && isset($forecast_data['list'])) {
    foreach ($forecast_data['list'] as $item) {
        $date = date('Y-m-d', $item['dt']);
        if (!isset($daily_forecast[$date])) {
            $daily_forecast[$date] = [
                'date' => $date,
                'temp_min' => $item['main']['temp_min'],
                'temp_max' => $item['main']['temp_max'],
                'condition' => $item['weather'][0]['main'],
                'description' => $item['weather'][0]['description'],
                'icon' => $item['weather'][0]['icon'],
                'pop' => $item['pop'] * 100 ?? 0, // Probability of precipitation
                'items' => [$item]
            ];
        } else {
            $daily_forecast[$date]['items'][] = $item;
            if ($item['main']['temp_min'] < $daily_forecast[$date]['temp_min']) {
                $daily_forecast[$date]['temp_min'] = $item['main']['temp_min'];
            }
            if ($item['main']['temp_max'] > $daily_forecast[$date]['temp_max']) {
                $daily_forecast[$date]['temp_max'] = $item['main']['temp_max'];
            }
        }
    }
    ksort($daily_forecast);
    $daily_forecast = array_slice($daily_forecast, 0, 10);
}

// Get current time
$current_time = date('H:i');
$current_date = date('d F Y');

// Determine if it's user's location
$is_my_location = ($lat && $lon) || (isset($_SESSION['user_lat']) && isset($_SESSION['user_lon']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(180deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            min-height: 100vh;
            padding: 0;
            overflow-x: hidden;
        }
        
        .weather-container {
            position: relative;
            min-height: 100vh;
            padding: 1rem;
            padding-bottom: 80px;
        }
        
        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            margin-bottom: 1rem;
        }
        
        .time-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .status-icons {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Main Weather Display */
        .main-weather {
            text-align: center;
            margin: 2rem 0;
        }
        
        .location-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .location-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .temperature-large {
            font-size: 5rem;
            font-weight: 300;
            line-height: 1;
            margin: 1rem 0;
        }
        
        .weather-condition {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            text-transform: capitalize;
        }
        
        .temp-details {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        /* Info Box */
        .info-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.25rem;
            margin: 1.5rem 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .info-text {
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        /* Hourly Forecast */
        .hourly-section {
            margin: 2rem 0;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .hourly-scroll {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: 1rem 0;
            -webkit-overflow-scrolling: touch;
        }
        
        .hourly-scroll::-webkit-scrollbar {
            display: none;
        }
        
        .hourly-item {
            min-width: 70px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem 0.75rem;
        }
        
        .hour-label {
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            opacity: 0.9;
        }
        
        .hour-icon {
            width: 40px;
            height: 40px;
            margin: 0.5rem auto;
        }
        
        .hour-pop {
            font-size: 0.75rem;
            margin: 0.5rem 0;
            opacity: 0.8;
        }
        
        .hour-temp {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        /* 10-Day Forecast */
        .daily-section {
            margin: 2rem 0;
        }
        
        .daily-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .daily-item:last-child {
            border-bottom: none;
        }
        
        .daily-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        
        .daily-day {
            min-width: 60px;
            font-weight: 600;
        }
        
        .daily-icon {
            width: 32px;
            height: 32px;
        }
        
        .daily-pop {
            font-size: 0.85rem;
            opacity: 0.8;
            min-width: 45px;
        }
        
        .daily-temps {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 100px;
        }
        
        .temp-bar {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            position: relative;
            overflow: hidden;
        }
        
        .temp-bar-fill {
            height: 100%;
            background: #ff9500;
            border-radius: 2px;
        }
        
        .daily-temp-range {
            display: flex;
            gap: 0.5rem;
            font-size: 0.9rem;
            min-width: 80px;
            justify-content: flex-end;
        }
        
        .temp-min {
            opacity: 0.7;
        }
        
        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(30, 58, 138, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem;
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-item {
            color: white;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .nav-item.active,
        .nav-item:hover {
            opacity: 1;
        }
        
        .nav-item i {
            font-size: 1.5rem;
        }
        
        .nav-dots {
            display: flex;
            gap: 0.25rem;
        }
        
        .nav-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
        }
        
        .nav-dot.active {
            background: white;
        }
    </style>
</head>
<body>
    <div class="weather-container">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="time-location">
                <span><?php echo $current_time; ?></span>
                <i class="bi bi-geo-alt-fill"></i>
            </div>
            <div class="status-icons">
                <i class="bi bi-signal"></i>
                <i class="bi bi-wifi"></i>
                <i class="bi bi-battery-half"></i>
            </div>
        </div>
        
        <!-- Main Weather -->
        <div class="main-weather">
            <div class="location-label"><?php echo $is_my_location ? 'LOKASI SAYA' : 'LOKASI'; ?></div>
            <div class="location-name"><?php echo htmlspecialchars($weather_data['name'] ?? $location); ?></div>
            
            <?php if ($weather_data && isset($weather_data['main'])): ?>
            <div class="temperature-large"><?php echo round($weather_data['main']['temp']); ?>°</div>
            <div class="weather-condition"><?php echo ucfirst($weather_data['weather'][0]['description'] ?? 'Clear'); ?></div>
            <div class="temp-details">
                T:<?php echo round($weather_data['main']['temp']); ?>° 
                R:<?php echo round($weather_data['main']['feels_like']); ?>°
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Info Box -->
        <?php if ($forecast_data && isset($forecast_data['list'][0])): 
            $next_forecast = $forecast_data['list'][0];
            $next_time = date('H:i', $next_forecast['dt']);
            $next_condition = ucfirst($next_forecast['weather'][0]['description']);
            $wind_speed = round($next_forecast['wind']['speed'] ?? 0);
        ?>
        <div class="info-box">
            <div class="info-text">
                Kondisi <?php echo strtolower($next_condition); ?> diprediksi sekitar <?php echo $next_time; ?>. 
                Embusan angin hingga <?php echo $wind_speed; ?> km/j.
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Hourly Forecast -->
        <?php if (!empty($hourly_forecast)): ?>
        <div class="hourly-section">
            <div class="section-title">
                <i class="bi bi-clock"></i>
                <span>Prakiraan Per Jam</span>
            </div>
            <div class="hourly-scroll">
                <div class="hourly-item">
                    <div class="hour-label">Sekarang</div>
                    <?php if ($weather_data && isset($weather_data['weather'][0])): ?>
                    <img src="https://openweathermap.org/img/wn/<?php echo $weather_data['weather'][0]['icon']; ?>.png" alt="" class="hour-icon">
                    <div class="hour-temp"><?php echo round($weather_data['main']['temp']); ?>°</div>
                    <?php endif; ?>
                </div>
                <?php foreach ($hourly_forecast as $hour): ?>
                <div class="hourly-item">
                    <div class="hour-label"><?php echo date('H', $hour['dt']); ?></div>
                    <img src="https://openweathermap.org/img/wn/<?php echo $hour['weather'][0]['icon']; ?>.png" alt="" class="hour-icon">
                    <div class="hour-pop"><?php echo round(($hour['pop'] ?? 0) * 100); ?>%</div>
                    <div class="hour-temp"><?php echo round($hour['main']['temp']); ?>°</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 10-Day Forecast -->
        <?php if (!empty($daily_forecast)): ?>
        <div class="daily-section">
            <div class="section-title">
                <i class="bi bi-calendar3"></i>
                <span>RAMALAN 10 HARI</span>
            </div>
            <?php 
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $is_today = true;
            foreach ($daily_forecast as $day): 
                $day_name = $is_today ? 'Hari Ini' : $days[date('w', strtotime($day['date']))];
                $is_today = false;
                $temp_min = round($day['temp_min']);
                $temp_max = round($day['temp_max']);
                $temp_range = $temp_max - $temp_min;
                $max_range = 15; // Assume max 15 degree range for visualization
                $bar_width = min(100, ($temp_range / $max_range) * 100);
            ?>
            <div class="daily-item">
                <div class="daily-left">
                    <div class="daily-day"><?php echo $day_name; ?></div>
                    <img src="https://openweathermap.org/img/wn/<?php echo $day['icon']; ?>.png" alt="" class="daily-icon">
                    <div class="daily-pop"><?php echo round($day['pop']); ?>%</div>
                </div>
                <div class="daily-temps">
                    <div class="temp-bar">
                        <div class="temp-bar-fill" style="width: <?php echo $bar_width; ?>%"></div>
                    </div>
                    <div class="daily-temp-range">
                        <span class="temp-min"><?php echo $temp_min; ?>°</span>
                        <span><?php echo $temp_max; ?>°</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="<?php echo base_url('dashboard.php'); ?>" class="nav-item">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>
        <div class="nav-item active">
            <div class="nav-dots">
                <span class="nav-dot active"></span>
                <span class="nav-dot"></span>
                <span class="nav-dot"></span>
            </div>
            <span>1</span>
        </div>
        <a href="<?php echo base_url('activities/index.php'); ?>" class="nav-item">
            <i class="bi bi-list-ul"></i>
            <span>List</span>
        </a>
    </div>
    
    <script>
        // Auto refresh every 10 minutes
        setTimeout(function() {
            location.reload();
        }, 600000);
    </script>
</body>
</html>

