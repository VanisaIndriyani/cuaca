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
        $condition_lower = strtolower($condition);
        
        // Prioritas: Kondisi cuaca lebih penting dari suhu
        // 1. Cek kondisi hujan/badai terlebih dahulu
        if (strpos($condition_lower, 'rain') !== false || 
            strpos($condition_lower, 'drizzle') !== false || 
            strpos($condition_lower, 'thunderstorm') !== false ||
            strpos($condition_lower, 'storm') !== false ||
            strpos($condition_lower, 'hujan') !== false ||
            strpos($condition_lower, 'badai') !== false) {
            
            // Cuaca hujan/badai - aktivitas indoor
            $recommendations[] = [
                'activity' => 'Membaca buku',
                'reason' => 'Cuaca hujan, cocok untuk aktivitas dalam ruangan',
                'category' => 'pendidikan'
            ];
            $recommendations[] = [
                'activity' => 'Yoga atau olahraga ringan indoor',
                'reason' => 'Hindari aktivitas luar ruangan saat hujan',
                'category' => 'olahraga'
            ];
            $recommendations[] = [
                'activity' => 'Belajar atau bekerja di dalam ruangan',
                'reason' => 'Cuaca tidak mendukung aktivitas outdoor',
                'category' => 'pendidikan'
            ];
            
        } elseif (strpos($condition_lower, 'snow') !== false || 
                  strpos($condition_lower, 'sleet') !== false ||
                  strpos($condition_lower, 'salju') !== false) {
            
            // Cuaca salju - aktivitas indoor
            $recommendations[] = [
                'activity' => 'Aktivitas indoor',
                'reason' => 'Cuaca sangat dingin dengan salju, tetap di dalam ruangan',
                'category' => 'istirahat'
            ];
            $recommendations[] = [
                'activity' => 'Minum teh hangat dan istirahat',
                'reason' => 'Cuaca ekstrem, hindari keluar ruangan',
                'category' => 'istirahat'
            ];
            
        } elseif (strpos($condition_lower, 'clear') !== false || 
                  strpos($condition_lower, 'sunny') !== false ||
                  strpos($condition_lower, 'cerah') !== false) {
            
            // Cuaca cerah - berdasarkan suhu
            if ($temperature >= 32) {
                // Sangat panas
                $recommendations[] = [
                    'activity' => 'Berenang',
                    'reason' => 'Suhu sangat panas, aktivitas air sangat cocok',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Istirahat di tempat teduh',
                    'reason' => 'Hindari aktivitas di bawah terik matahari langsung',
                    'category' => 'istirahat'
                ];
                $recommendations[] = [
                    'activity' => 'Minum banyak air putih',
                    'reason' => 'Suhu tinggi, penting untuk tetap terhidrasi',
                    'category' => 'istirahat'
                ];
            } elseif ($temperature >= 28 && $temperature < 32) {
                // Panas
                $recommendations[] = [
                    'activity' => 'Jogging pagi atau sore',
                    'reason' => 'Suhu panas, lakukan olahraga di waktu yang lebih sejuk',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Bersepeda dengan topi dan tabir surya',
                    'reason' => 'Cuaca cerah tapi panas, gunakan perlindungan',
                    'category' => 'olahraga'
                ];
            } elseif ($temperature >= 24 && $temperature < 28) {
                // Hangat - ideal
                $recommendations[] = [
                    'activity' => 'Jogging',
                    'reason' => 'Suhu ideal untuk olahraga outdoor',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Bersepeda',
                    'reason' => 'Cuaca cerah dan nyaman untuk aktivitas luar ruangan',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Piknik atau aktivitas outdoor',
                    'reason' => 'Cuaca sempurna untuk aktivitas di luar',
                    'category' => 'lainnya'
                ];
            } elseif ($temperature >= 20 && $temperature < 24) {
                // Sejuk
                $recommendations[] = [
                    'activity' => 'Futsal atau sepak bola',
                    'reason' => 'Suhu sejuk, cocok untuk olahraga tim',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Jogging atau lari',
                    'reason' => 'Cuaca cerah dan sejuk, ideal untuk olahraga',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Aktivitas outdoor lainnya',
                    'reason' => 'Cuaca cerah dan nyaman',
                    'category' => 'olahraga'
                ];
            } elseif ($temperature >= 15 && $temperature < 20) {
                // Dingin
                $recommendations[] = [
                    'activity' => 'Jogging dengan pakaian hangat',
                    'reason' => 'Cuaca cerah tapi dingin, gunakan pakaian yang tepat',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Aktivitas outdoor ringan',
                    'reason' => 'Cuaca cerah, bisa beraktivitas dengan pakaian hangat',
                    'category' => 'olahraga'
                ];
            } else {
                // Sangat dingin
                $recommendations[] = [
                    'activity' => 'Aktivitas indoor',
                    'reason' => 'Suhu sangat dingin, lebih baik aktivitas dalam ruangan',
                    'category' => 'istirahat'
                ];
                $recommendations[] = [
                    'activity' => 'Olahraga di gym atau dalam ruangan',
                    'reason' => 'Cuaca terlalu dingin untuk aktivitas outdoor',
                    'category' => 'olahraga'
                ];
            }
            
        } elseif (strpos($condition_lower, 'cloud') !== false || 
                  strpos($condition_lower, 'overcast') !== false ||
                  strpos($condition_lower, 'berawan') !== false ||
                  strpos($condition_lower, 'mendung') !== false) {
            
            // Cuaca berawan - berdasarkan suhu
            if ($temperature >= 28) {
                $recommendations[] = [
                    'activity' => 'Jogging atau olahraga outdoor',
                    'reason' => 'Cuaca berawan, tidak terlalu panas untuk aktivitas luar',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Bersepeda',
                    'reason' => 'Awan membantu mengurangi panas matahari',
                    'category' => 'olahraga'
                ];
            } elseif ($temperature >= 24 && $temperature < 28) {
                $recommendations[] = [
                    'activity' => 'Jogging',
                    'reason' => 'Cuaca berawan dan nyaman untuk olahraga',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Aktivitas outdoor',
                    'reason' => 'Cuaca berawan, suhu nyaman',
                    'category' => 'olahraga'
                ];
            } elseif ($temperature >= 20 && $temperature < 24) {
                $recommendations[] = [
                    'activity' => 'Futsal atau olahraga tim',
                    'reason' => 'Cuaca berawan dan sejuk, cocok untuk olahraga',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Jogging',
                    'reason' => 'Suhu sejuk dengan cuaca berawan',
                    'category' => 'olahraga'
                ];
            } else {
                $recommendations[] = [
                    'activity' => 'Aktivitas indoor',
                    'reason' => 'Cuaca berawan dan dingin, lebih baik di dalam ruangan',
                    'category' => 'istirahat'
                ];
            }
            
        } elseif (strpos($condition_lower, 'mist') !== false || 
                  strpos($condition_lower, 'fog') !== false ||
                  strpos($condition_lower, 'haze') !== false ||
                  strpos($condition_lower, 'kabut') !== false) {
            
            // Cuaca berkabut
            $recommendations[] = [
                'activity' => 'Aktivitas indoor',
                'reason' => 'Cuaca berkabut, visibilitas rendah, hindari aktivitas luar',
                'category' => 'istirahat'
            ];
            $recommendations[] = [
                'activity' => 'Olahraga dalam ruangan',
                'reason' => 'Kabut mengurangi visibilitas, lebih aman di dalam',
                'category' => 'olahraga'
            ];
            
        } else {
            // Kondisi cuaca lainnya - berdasarkan suhu saja
            if ($temperature >= 28) {
                $recommendations[] = [
                    'activity' => 'Aktivitas dengan perlindungan matahari',
                    'reason' => 'Suhu tinggi, gunakan topi dan tabir surya',
                    'category' => 'olahraga'
                ];
            } elseif ($temperature >= 24 && $temperature < 28) {
                $recommendations[] = [
                    'activity' => 'Jogging',
                    'reason' => 'Suhu nyaman untuk olahraga outdoor',
                    'category' => 'olahraga'
                ];
                $recommendations[] = [
                    'activity' => 'Bersepeda',
                    'reason' => 'Cuaca nyaman untuk aktivitas luar ruangan',
                    'category' => 'olahraga'
                ];
            } elseif ($temperature >= 20 && $temperature < 24) {
                $recommendations[] = [
                    'activity' => 'Futsal',
                    'reason' => 'Suhu sejuk, cocok untuk olahraga tim',
                    'category' => 'olahraga'
                ];
            } else {
                $recommendations[] = [
                    'activity' => 'Aktivitas indoor',
                    'reason' => 'Suhu dingin, lebih baik aktivitas dalam ruangan',
                    'category' => 'istirahat'
                ];
            }
        }
        
        // Hapus duplikat dan return maksimal 3 rekomendasi
        $unique_recommendations = [];
        $seen_activities = [];
        foreach ($recommendations as $rec) {
            if (!in_array($rec['activity'], $seen_activities)) {
                $unique_recommendations[] = $rec;
                $seen_activities[] = $rec['activity'];
            }
        }
        
        return array_slice($unique_recommendations, 0, 3);
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

