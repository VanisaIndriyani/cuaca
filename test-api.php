<?php
/**
 * Test Script untuk OpenWeatherMap API
 * Akses: http://localhost/cuaca/test-api.php
 */

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$api_key = $_ENV['OWM_API_KEY'] ?? '';
$location = 'Jakarta';

echo "<h2>Test OpenWeatherMap API</h2>";
echo "<hr>";

// Test 1: Check API Key
echo "<h3>1. Cek API Key</h3>";
if (empty($api_key)) {
    echo "<p style='color:red;'>❌ API Key TIDAK DITEMUKAN di .env</p>";
    echo "<p>Pastikan file .env berisi: <code>OWM_API_KEY=your_key_here</code></p>";
} else {
    echo "<p style='color:green;'>✅ API Key ditemukan</p>";
    echo "<p>API Key: <code>" . substr($api_key, 0, 10) . "..." . substr($api_key, -5) . "</code> (panjang: " . strlen($api_key) . " karakter)</p>";
}
echo "<hr>";

// Test 2: Current Weather
echo "<h3>2. Test Current Weather API</h3>";
$url_current = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid=" . $api_key . "&units=metric&lang=id";

// Check if CURL is available
if (!function_exists('curl_init')) {
    echo "<p style='color:red;'>❌ CURL extension tidak tersedia di PHP</p>";
    echo "<p>Gunakan alternatif file_get_contents...</p>";
    
    // Try with file_get_contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    $response_current = @file_get_contents($url_current, false, $context);
    $http_code_current = $response_current ? 200 : 0;
} else {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_current);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response_current = curl_exec($ch);
    $http_code_current = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    
    if ($curl_errno) {
        echo "<p style='color:orange;'>⚠️ CURL Error #{$curl_errno}: <strong>{$curl_error}</strong></p>";
    }
}

echo "<p>URL: <code>" . htmlspecialchars($url_current) . "</code></p>";
echo "<p>HTTP Code: <strong>" . $http_code_current . "</strong></p>";

if ($http_code_current === 0) {
    echo "<p style='color:red;'>❌ GAGAL TERHUBUNG KE API</p>";
    echo "<p><strong>Kemungkinan penyebab:</strong></p>";
    echo "<ul>";
    echo "<li>❌ Tidak ada koneksi internet</li>";
    echo "<li>❌ Firewall/Antivirus memblokir koneksi</li>";
    echo "<li>❌ CURL extension tidak aktif di PHP</li>";
    echo "<li>❌ Proxy settings bermasalah</li>";
    echo "<li>❌ SSL/TLS certificate issue</li>";
    echo "</ul>";
    echo "<p><strong>Solusi:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Cek koneksi internet Anda</li>";
    echo "<li>✅ Matikan sementara firewall/antivirus</li>";
    echo "<li>✅ Pastikan CURL extension aktif di php.ini</li>";
    echo "<li>✅ Cek proxy settings jika menggunakan proxy</li>";
    echo "</ul>";
} elseif ($http_code_current === 200) {
    $data_current = json_decode($response_current, true);
    if ($data_current) {
        echo "<p style='color:green;'>✅ Current Weather API BERHASIL</p>";
        echo "<pre>" . print_r($data_current, true) . "</pre>";
    } else {
        echo "<p style='color:red;'>❌ Gagal decode JSON</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Current Weather API GAGAL</p>";
    if ($response_current) {
        $error = json_decode($response_current, true);
        $error_msg = $error['message'] ?? $response_current;
        echo "<p>Error: <code>" . htmlspecialchars($error_msg) . "</code></p>";
        
        // Special handling for 401 Invalid API key
        if ($http_code_current === 401) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
            echo "<h4 style='color: #856404; margin-top: 0;'>⚠️ API Key Tidak Valid!</h4>";
            echo "<p><strong>Solusi:</strong></p>";
            echo "<ol>";
            echo "<li>Buka file <code>.env</code> di root folder</li>";
            echo "<li>Pastikan baris: <code>OWM_API_KEY=4a8ea63a0dc8e6543e9ea4e81949c502</code></li>";
            echo "<li><strong>RESTART web server</strong> (Laragon: Stop All → Start All)</li>";
            echo "<li>Refresh halaman ini</li>";
            echo "</ol>";
            echo "<p><strong>Atau dapatkan API key baru di:</strong> <a href='https://home.openweathermap.org/api_keys' target='_blank'>https://home.openweathermap.org/api_keys</a></p>";
            echo "</div>";
        }
    } elseif ($http_code_current === 0) {
        echo "<div style='background: #f8d7da; border: 1px solid #dc3545; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
        echo "<h4 style='color: #721c24; margin-top: 0;'>⚠️ Tidak Bisa Terhubung!</h4>";
        echo "<p>Kemungkinan: Tidak ada koneksi internet atau firewall memblokir.</p>";
        echo "</div>";
    }
}
echo "<hr>";

// Test 3: Forecast API
echo "<h3>3. Test Forecast API</h3>";
$url_forecast = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($location) . "&appid=" . $api_key . "&units=metric&lang=id&cnt=40";

// Check if CURL is available
if (!function_exists('curl_init')) {
    echo "<p style='color:red;'>❌ CURL extension tidak tersedia di PHP</p>";
    echo "<p>Gunakan alternatif file_get_contents...</p>";
    
    // Try with file_get_contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    $response_forecast = @file_get_contents($url_forecast, false, $context);
    $http_code_forecast = $response_forecast ? 200 : 0;
} else {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_forecast);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response_forecast = curl_exec($ch);
    $http_code_forecast = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    
    if ($curl_errno) {
        echo "<p style='color:orange;'>⚠️ CURL Error #{$curl_errno}: <strong>{$curl_error}</strong></p>";
    }
}

echo "<p>URL: <code>" . htmlspecialchars($url_forecast) . "</code></p>";
echo "<p>HTTP Code: <strong>" . $http_code_forecast . "</strong></p>";

if ($http_code_forecast === 0) {
    echo "<p style='color:red;'>❌ GAGAL TERHUBUNG KE API</p>";
    echo "<p><strong>Kemungkinan penyebab sama seperti Current Weather API di atas.</strong></p>";
} elseif ($http_code_forecast === 200) {
    $data_forecast = json_decode($response_forecast, true);
    if ($data_forecast && isset($data_forecast['list'])) {
        echo "<p style='color:green;'>✅ Forecast API BERHASIL</p>";
        echo "<p>Jumlah forecast items: <strong>" . count($data_forecast['list']) . "</strong></p>";
        echo "<p>City: <strong>" . ($data_forecast['city']['name'] ?? 'N/A') . "</strong></p>";
        echo "<h4>Sample Forecast Data (3 pertama):</h4>";
        echo "<pre>" . print_r(array_slice($data_forecast['list'], 0, 3), true) . "</pre>";
        
        // Test grouping by day
        echo "<h4>Test Grouping by Day:</h4>";
        $daily_forecast = [];
        foreach ($data_forecast['list'] as $item) {
            if (isset($item['dt'])) {
                $date = date('Y-m-d', $item['dt']);
                if (!isset($daily_forecast[$date])) {
                    $daily_forecast[$date] = $item;
                }
            }
        }
        ksort($daily_forecast);
        $daily_forecast = array_slice($daily_forecast, 0, 7);
        echo "<p>Jumlah hari unik: <strong>" . count($daily_forecast) . "</strong></p>";
        echo "<pre>" . print_r($daily_forecast, true) . "</pre>";
    } else {
        echo "<p style='color:red;'>❌ Forecast data tidak valid atau tidak ada 'list'</p>";
        if ($data_forecast) {
            echo "<pre>" . print_r($data_forecast, true) . "</pre>";
        }
    }
} else {
    echo "<p style='color:red;'>❌ Forecast API GAGAL</p>";
    if ($response_forecast) {
        $error = json_decode($response_forecast, true);
        $error_msg = $error['message'] ?? $response_forecast;
        echo "<p>Error: <code>" . htmlspecialchars($error_msg) . "</code></p>";
        
        // Special handling for 401 Invalid API key
        if ($http_code_forecast === 401) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
            echo "<h4 style='color: #856404; margin-top: 0;'>⚠️ API Key Tidak Valid!</h4>";
            echo "<p>Solusi sama seperti di atas - perbaiki API key di file <code>.env</code> dan restart web server.</p>";
            echo "</div>";
        }
    } elseif ($http_code_forecast === 0) {
        echo "<div style='background: #f8d7da; border: 1px solid #dc3545; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
        echo "<h4 style='color: #721c24; margin-top: 0;'>⚠️ Tidak Bisa Terhubung!</h4>";
        echo "<p>Kemungkinan: Tidak ada koneksi internet atau firewall memblokir.</p>";
        echo "</div>";
    }
}
echo "<hr>";

// Test 4: Check Cache
echo "<h3>4. Cek Cache Directory</h3>";
$cache_dir = __DIR__ . '/public/cache/';
if (is_dir($cache_dir)) {
    echo "<p style='color:green;'>✅ Cache directory ada</p>";
    $cache_files = glob($cache_dir . '*.json');
    echo "<p>Jumlah file cache: <strong>" . count($cache_files) . "</strong></p>";
    if (count($cache_files) > 0) {
        echo "<p>File cache terbaru:</p><ul>";
        foreach (array_slice($cache_files, 0, 5) as $file) {
            echo "<li>" . basename($file) . " (modified: " . date('Y-m-d H:i:s', filemtime($file)) . ")</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color:orange;'>⚠️ Cache directory tidak ada, akan dibuat otomatis</p>";
}
echo "<hr>";

echo "<p><a href='dashboard.php'>← Kembali ke Dashboard</a></p>";

