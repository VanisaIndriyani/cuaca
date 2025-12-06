<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Models/WeatherData.php';
require_once __DIR__ . '/../Models/Activity.php';

class AnalyticsService {
    private $conn;
    private $weatherData;
    private $activity;

    public function __construct($db) {
        $this->conn = $db;
        $this->weatherData = new WeatherData($db);
        $this->activity = new Activity($db);
    }

    public function getWeeklyAverageTemperature($location) {
        $data = $this->weatherData->getAverageByWeek($location);
        $total_temp = 0;
        $count = 0;
        
        foreach ($data as $row) {
            $total_temp += $row['avg_temp'];
            $count++;
        }
        
        return $count > 0 ? round($total_temp / $count, 1) : 0;
    }

    public function getWeeklyAverageHumidity($location) {
        $data = $this->weatherData->getAverageByWeek($location);
        $total_humidity = 0;
        $count = 0;
        
        foreach ($data as $row) {
            $total_humidity += $row['avg_humidity'];
            $count++;
        }
        
        return $count > 0 ? round($total_humidity / $count, 1) : 0;
    }

    public function getActivityRecommendations($temperature, $condition) {
        $recommendations = [];
        
        // Berdasarkan suhu
        if ($temperature >= 30) {
            $recommendations[] = [
                'activity' => 'Berenang',
                'reason' => 'Suhu panas, cocok untuk aktivitas air',
                'category' => 'olahraga'
            ];
            $recommendations[] = [
                'activity' => 'Istirahat di tempat teduh',
                'reason' => 'Hindari aktivitas di bawah terik matahari',
                'category' => 'istirahat'
            ];
        } elseif ($temperature >= 25 && $temperature < 30) {
            $recommendations[] = [
                'activity' => 'Jogging',
                'reason' => 'Suhu ideal untuk olahraga outdoor',
                'category' => 'olahraga'
            ];
            $recommendations[] = [
                'activity' => 'Bersepeda',
                'reason' => 'Cuaca nyaman untuk aktivitas luar ruangan',
                'category' => 'olahraga'
            ];
        } elseif ($temperature >= 20 && $temperature < 25) {
            $recommendations[] = [
                'activity' => 'Futsal',
                'reason' => 'Suhu sejuk, cocok untuk olahraga tim',
                'category' => 'olahraga'
            ];
            $recommendations[] = [
                'activity' => 'Kuliah',
                'reason' => 'Cuaca nyaman untuk aktivitas belajar',
                'category' => 'pendidikan'
            ];
        } else {
            $recommendations[] = [
                'activity' => 'Aktivitas indoor',
                'reason' => 'Suhu dingin, lebih baik aktivitas dalam ruangan',
                'category' => 'lainnya'
            ];
        }
        
        // Berdasarkan kondisi cuaca
        $condition_lower = strtolower($condition);
        if (strpos($condition_lower, 'rain') !== false || strpos($condition_lower, 'hujan') !== false) {
            $recommendations[] = [
                'activity' => 'Aktivitas indoor',
                'reason' => 'Hujan, hindari aktivitas luar ruangan',
                'category' => 'lainnya'
            ];
        } elseif (strpos($condition_lower, 'clear') !== false || strpos($condition_lower, 'cerah') !== false) {
            $recommendations[] = [
                'activity' => 'Outdoor activities',
                'reason' => 'Cuaca cerah, sempurna untuk aktivitas luar',
                'category' => 'olahraga'
            ];
        }
        
        return array_slice($recommendations, 0, 3); // Return top 3
    }

    public function getActivityStatsByCategory($user_id = null) {
        return $this->activity->getByCategory($user_id);
    }

    public function getTemperatureTrend($location, $days = 7) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        return $this->weatherData->getByDateRange($location, $start_date, $end_date);
    }

    public function getHumidityTrend($location, $days = 7) {
        return $this->getTemperatureTrend($location, $days); // Same data, different field
    }

    public function generateCSVReport($user_id, $start_date, $end_date) {
        $activities = $this->activity->getByDateRange($user_id, $start_date, $end_date);
        
        // Add UTF-8 BOM for Excel compatibility
        $csv = "\xEF\xBB\xBF";
        
        // Header
        $csv .= "Tanggal,Waktu Mulai,Waktu Selesai,Aktivitas,Kategori,Lokasi,Deskripsi\n";
        
        // Data rows
        foreach ($activities as $act) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $this->escapeCSV($act['activity_date'] ?? ''),
                $this->escapeCSV($act['start_time'] ?? ''),
                $this->escapeCSV($act['end_time'] ?? ''),
                $this->escapeCSV($act['title'] ?? ''),
                $this->escapeCSV($act['category'] ?? ''),
                $this->escapeCSV($act['location'] ?? ''),
                $this->escapeCSV($act['description'] ?? '')
            );
        }
        
        return $csv;
    }
    
    private function escapeCSV($field) {
        // If field contains comma, quote, or newline, wrap in quotes and escape quotes
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }
}

