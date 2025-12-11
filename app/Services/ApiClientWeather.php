<?php
require_once __DIR__ . '/../../config/config.php';

class ApiClientWeather {
    private $api_key;
    private $cache_dir;
    private $cache_ttl; // Time to live in seconds (default: 10 minutes)

    public function __construct() {
        $this->loadEnv();
        $this->api_key = $_ENV['OWM_API_KEY'] ?? '';
        $this->cache_dir = __DIR__ . '/../../public/cache/';
        $this->cache_ttl = 300; // 5 minutes (reduced for better accuracy)
        
        // Create cache directory if not exists
        if (!file_exists($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    private function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
    }

    private function getCacheKey($location) {
        return md5('weather_' . strtolower($location));
    }

    private function getCacheFile($location) {
        return $this->cache_dir . $this->getCacheKey($location) . '.json';
    }

    private function getCachedData($location) {
        $cache_file = $this->getCacheFile($location);
        
        if (file_exists($cache_file)) {
            $cache_time = filemtime($cache_file);
            if (time() - $cache_time < $this->cache_ttl) {
                $data = json_decode(file_get_contents($cache_file), true);
                return $data;
            }
        }
        return null;
    }

    private function saveCache($location, $data) {
        $cache_file = $this->getCacheFile($location);
        file_put_contents($cache_file, json_encode($data));
    }

    public function fetchCurrentWeather($location, $force_refresh = false) {
        // Check cache first (unless force refresh)
        if (!$force_refresh) {
            $cached = $this->getCachedData($location);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Fetch from API
        $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid=" . $this->api_key . "&units=metric&lang=id";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("CURL Error in fetchCurrentWeather: " . $curl_error);
        }

        if ($http_code === 200) {
            $data = json_decode($response, true);
            if ($data) {
                $this->saveCache($location, $data);
                return $data;
            }
        }
        
        return null;
    }

    public function fetchWeatherByCoords($lat, $lon, $force_refresh = false) {
        $cache_key = "coords_{$lat}_{$lon}";
        // Check cache first (unless force refresh)
        if (!$force_refresh) {
            $cached = $this->getCachedData($cache_key);
            if ($cached !== null) {
                return $cached;
            }
        }

        $url = "https://api.openweathermap.org/data/2.5/weather?lat=" . $lat . "&lon=" . $lon . "&appid=" . $this->api_key . "&units=metric&lang=id";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("CURL Error in fetchCurrentWeather: " . $curl_error);
        }

        if ($http_code === 200) {
            $data = json_decode($response, true);
            if ($data) {
                $this->saveCache($cache_key, $data);
                return $data;
            }
        }
        
        return null;
    }

    public function fetchForecast($location, $days = 5) {
        if (empty($this->api_key)) {
            error_log("OpenWeatherMap API key is not set");
            return null;
        }

        $cache_key = "forecast_" . $location . "_" . $days;
        $cached = $this->getCachedData($cache_key);
        if ($cached !== null) {
            return $cached;
        }

        // First, get coordinates for the location to use with One Call API
        $weather_data = $this->fetchCurrentWeather($location);
        if ($weather_data && isset($weather_data['coord'])) {
            $lat = $weather_data['coord']['lat'];
            $lon = $weather_data['coord']['lon'];
            
            // Try One Call API 3.0 first (for 7+ days forecast)
            if ($days >= 7) {
                $one_call_url = "https://api.openweathermap.org/data/3.0/onecall?lat=" . $lat . "&lon=" . $lon . "&appid=" . $this->api_key . "&units=metric&lang=id&exclude=minutely,hourly,alerts";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $one_call_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($http_code === 200 && !$curl_error) {
                    $data = json_decode($response, true);
                    if ($data && isset($data['daily']) && is_array($data['daily'])) {
                        // Convert One Call format to forecast format
                        $forecast_list = [];
                        foreach (array_slice($data['daily'], 0, $days) as $daily) {
                            $forecast_list[] = [
                                'dt' => $daily['dt'],
                                'main' => [
                                    'temp' => $daily['temp']['day'],
                                    'temp_min' => $daily['temp']['min'],
                                    'temp_max' => $daily['temp']['max'],
                                    'feels_like' => $daily['feels_like']['day'],
                                    'pressure' => $daily['pressure'],
                                    'humidity' => $daily['humidity']
                                ],
                                'weather' => $daily['weather'],
                                'wind' => [
                                    'speed' => $daily['wind_speed'] ?? 0,
                                    'deg' => $daily['wind_deg'] ?? 0
                                ],
                                'pop' => $daily['pop'] ?? 0
                            ];
                        }
                        $result = [
                            'list' => $forecast_list,
                            'city' => [
                                'name' => $location,
                                'coord' => ['lat' => $lat, 'lon' => $lon]
                            ]
                        ];
                        $this->saveCache($cache_key, $result);
                        return $result;
                    }
                }
            }
        }

        // Fallback to standard forecast API (max 5 days)
        // OpenWeatherMap forecast API returns 3-hour intervals, so for 7 days we need max 40 items
        $cnt = min($days * 8, 40);
        $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode($location) . "&appid=" . $this->api_key . "&units=metric&lang=id&cnt=" . $cnt;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log("CURL Error in fetchForecast: " . $curl_error);
            return null;
        }

        if ($http_code === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['list']) && is_array($data['list'])) {
                $this->saveCache($cache_key, $data);
                return $data;
            } else {
                error_log("Invalid forecast data structure for location: " . $location);
            }
        } else {
            error_log("Forecast API returned HTTP code: " . $http_code . " for location: " . $location);
            if ($response) {
                $error_data = json_decode($response, true);
                if (isset($error_data['message'])) {
                    error_log("API Error: " . $error_data['message']);
                }
            }
        }
        
        return null;
    }

    public function getLocationDetails($lat, $lon) {
        // Round coordinates to 4 decimal places for better caching (about 11 meters precision)
        $lat_rounded = round($lat, 4);
        $lon_rounded = round($lon, 4);
        $cache_key = "geocode_{$lat_rounded}_{$lon_rounded}";
        
        // Use longer cache for geocoding (1 hour)
        $cache_file = $this->getCacheFile($cache_key);
        if (file_exists($cache_file)) {
            $cache_time = filemtime($cache_file);
            if (time() - $cache_time < 3600) { // 1 hour cache
                $data = json_decode(file_get_contents($cache_file), true);
                return $data;
            }
        }

        // Try OpenStreetMap Nominatim first (more detailed for Indonesia)
        $nominatim_url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=" . $lat . "&lon=" . $lon . "&zoom=18&addressdetails=1&accept-language=id";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $nominatim_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: CuacaApp/1.0']);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code === 200 && !$curl_error) {
            $data = json_decode($response, true);
            if ($data && isset($data['address'])) {
                $addr = $data['address'];
                
                // Extract detailed location info
                // Prioritize village/desa for Indonesia
                $village = '';
                if (!empty($addr['village'])) {
                    $village = $addr['village'];
                } elseif (!empty($addr['suburb'])) {
                    $village = $addr['suburb'];
                } elseif (!empty($addr['neighbourhood'])) {
                    $village = $addr['neighbourhood'];
                } elseif (!empty($addr['hamlet'])) {
                    $village = $addr['hamlet'];
                }
                
                // Get subdistrict (kecamatan) - important for Indonesia
                // Kecamatan bisa ada di beberapa field di Nominatim
                $subdistrict = '';
                if (!empty($addr['subdistrict'])) {
                    $subdistrict = $addr['subdistrict'];
                } elseif (!empty($addr['county'])) {
                    $subdistrict = $addr['county'];
                } elseif (!empty($addr['city_district'])) {
                    $subdistrict = $addr['city_district'];
                }
                
                // Get city/kabupaten
                $city = '';
                if (!empty($addr['city'])) {
                    $city = $addr['city'];
                } elseif (!empty($addr['town'])) {
                    $city = $addr['town'];
                } elseif (!empty($addr['municipality'])) {
                    $city = $addr['municipality'];
                }
                
                $result = [
                    'name' => $data['display_name'] ?? '',
                    'display_name' => $data['display_name'] ?? '',
                    'village' => $village,
                    'district' => $addr['city_district'] ?? $addr['district'] ?? '',
                    'subdistrict' => $subdistrict,
                    'city' => $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? '',
                    'state' => $addr['state'] ?? $addr['province'] ?? '',
                    'country' => $addr['country'] ?? 'Indonesia',
                    'postcode' => $addr['postcode'] ?? '',
                    'university' => $addr['university'] ?? $addr['college'] ?? $addr['school'] ?? '',
                    'road' => $addr['road'] ?? '',
                    'house_number' => $addr['house_number'] ?? ''
                ];
                
                $this->saveCache($cache_key, $result);
                return $result;
            }
        }
        
        // Fallback to OpenWeatherMap Geocoding API
        $url = "http://api.openweathermap.org/geo/1.0/reverse?lat=" . $lat . "&lon=" . $lon . "&limit=1&appid=" . $this->api_key;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("CURL Error in getLocationDetails: " . $curl_error);
        }

        if ($http_code === 200) {
            $data = json_decode($response, true);
            if ($data && is_array($data) && count($data) > 0) {
                $location_data = $data[0];
                $result = [
                    'name' => $location_data['name'] ?? '',
                    'state' => $location_data['state'] ?? '',
                    'country' => $location_data['country'] ?? '',
                    'local_names' => $location_data['local_names'] ?? []
                ];
                $this->saveCache($cache_key, $result);
                return $result;
            }
        }
        
        return null;
    }

    public function formatLocationName($weather_data, $lat = null, $lon = null) {
        $location_name = $weather_data['name'] ?? 'Unknown';
        
        // If we have coordinates, try to get more detailed location info
        if ($lat && $lon) {
            $location_details = $this->getLocationDetails($lat, $lon);
            if ($location_details) {
                $parts = [];
                
                // Format: Desa, Kecamatan, Kota (HANYA 3 bagian, tidak lebih)
                // Urutan HARUS: Desa -> Kecamatan -> Kota
                
                // Priority 1: Village/Suburb/Neighbourhood (most specific) - Desa
                if (!empty($location_details['village'])) {
                    $parts[] = $location_details['village'];
                }
                
                // Priority 2: Subdistrict (Kecamatan) - HARUS setelah desa, SEBELUM kota
                if (!empty($location_details['subdistrict'])) {
                    $parts[] = $location_details['subdistrict'];
                } elseif (!empty($location_details['district'])) {
                    // Jika tidak ada subdistrict, gunakan district sebagai kecamatan
                    $parts[] = $location_details['district'];
                }
                
                // Priority 3: City/Kabupaten - HARUS setelah kecamatan (di akhir)
                // Hanya ambil city, jangan tambahkan state jika sudah ada city
                if (!empty($location_details['city'])) {
                    $parts[] = $location_details['city'];
                } elseif (!empty($location_details['state'])) {
                    // Jika city tidak ada, gunakan state (untuk Yogyakarta, state = "Yogyakarta")
                    // Tapi hanya jika state bukan provinsi besar
                    $state = $location_details['state'];
                    $large_provinces = ['Jawa Tengah', 'Jawa Barat', 'Jawa Timur', 'Sumatera Utara', 'Sumatera Selatan', 
                                       'Kalimantan Timur', 'Kalimantan Selatan', 'Sulawesi Selatan', 'Sulawesi Utara'];
                    // Untuk Yogyakarta, gunakan "Yogyakarta" bukan "Daerah Istimewa Yogyakarta"
                    if (stripos($state, 'Yogyakarta') !== false) {
                        $parts[] = 'Yogyakarta';
                    } elseif (!in_array($state, $large_provinces)) {
                        $parts[] = $state;
                    }
                }
                
                // JANGAN tambahkan university/college - biarkan hanya 3 bagian: Desa, Kecamatan, Kota
                
                // Build formatted location name
                if (!empty($parts)) {
                    // Remove duplicates while preserving order, and filter out empty
                    $seen = [];
                    $unique_parts = [];
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (!empty($part) && !in_array($part, $seen)) {
                            $seen[] = $part;
                            $unique_parts[] = $part;
                        }
                    }
                    $parts = $unique_parts;
                    
                    // Format: "Desa, Kecamatan, Kota" (HANYA 3 bagian, tidak lebih)
                    // Pastikan urutan: Desa -> Kecamatan -> Kota
                    // Jika lebih dari 3 bagian, ambil hanya: bagian pertama (Desa), bagian tengah (Kecamatan), bagian terakhir (Kota)
                    if (count($parts) > 3) {
                        $final_parts = [];
                        // Ambil bagian pertama sebagai Desa
                        if (count($parts) > 0) {
                            $final_parts[] = $parts[0];
                        }
                        // Ambil bagian tengah sebagai Kecamatan
                        if (count($parts) > 2) {
                            $middle_index = floor((count($parts) - 1) / 2);
                            if ($middle_index > 0 && $middle_index < count($parts) - 1) {
                                $final_parts[] = $parts[$middle_index];
                            } elseif (count($parts) > 1) {
                                $final_parts[] = $parts[1]; // Fallback
                            }
                        }
                        // Ambil bagian terakhir sebagai Kota
                        if (count($parts) > 1) {
                            $final_parts[] = end($parts);
                        }
                        $parts = array_filter($final_parts); // Hapus yang kosong
                    } elseif (count($parts) == 3) {
                        // Jika sudah 3 bagian, pastikan urutannya benar: Desa, Kecamatan, Kota
                        // Tidak perlu diubah
                    }
                    
                    $formatted = implode(', ', $parts);
                    
                    // Pastikan hanya 3 bagian: Desa, Kecamatan, Kota
                    // Jika lebih dari 3, ambil hanya: bagian pertama, tengah, terakhir
                    if (count($parts) > 3) {
                        $final_parts = [];
                        if (count($parts) > 0) {
                            $final_parts[] = $parts[0]; // Desa
                        }
                        if (count($parts) > 2) {
                            $middle = floor((count($parts) - 1) / 2);
                            if ($middle > 0 && $middle < count($parts) - 1) {
                                $final_parts[] = $parts[$middle]; // Kecamatan
                            } elseif (count($parts) > 1) {
                                $final_parts[] = $parts[1];
                            }
                        }
                        if (count($parts) > 1) {
                            $final_parts[] = end($parts); // Kota
                        }
                        $parts = array_filter($final_parts);
                        $formatted = implode(', ', $parts);
                    }
                    
                    // If formatted name is different from default and has content, use it
                    if ($formatted !== $location_name && strlen($formatted) > 0) {
                        // Pastikan hanya 3 bagian, tidak lebih
                        $formatted_parts = explode(', ', $formatted);
                        if (count($formatted_parts) > 3) {
                            // Ambil hanya: bagian pertama (Desa), tengah (Kecamatan), terakhir (Kota)
                            $final = [];
                            if (count($formatted_parts) > 0) {
                                $final[] = trim($formatted_parts[0]);
                            }
                            if (count($formatted_parts) > 2) {
                                $mid = floor((count($formatted_parts) - 1) / 2);
                                if ($mid > 0 && $mid < count($formatted_parts) - 1) {
                                    $final[] = trim($formatted_parts[$mid]);
                                } elseif (count($formatted_parts) > 1) {
                                    $final[] = trim($formatted_parts[1]);
                                }
                            }
                            if (count($formatted_parts) > 1) {
                                $final[] = trim(end($formatted_parts));
                            }
                            $formatted = implode(', ', $final);
                        }
                        
                        // Pastikan ada kota di akhir (HANYA jika kurang dari 3 bagian)
                        // JANGAN tambahkan jika sudah 3 bagian atau lebih
                        $formatted_parts_check = explode(', ', $formatted);
                        if (count($formatted_parts_check) < 3) {
                            $last_part = end($formatted_parts_check);
                            $has_city = false;
                            
                            // Cek apakah bagian terakhir adalah kota
                            $city_keywords = ['Kota', 'Kabupaten', 'Yogyakarta', 'Sleman', 'Bantul', 'Gunungkidul', 'Kulon Progo'];
                            foreach ($city_keywords as $keyword) {
                                if (stripos($last_part, $keyword) !== false) {
                                    $has_city = true;
                                    break;
                                }
                            }
                            
                            // Jika tidak ada kota dan masih kurang dari 3 bagian, tambahkan kota (maksimal sampai 3)
                            if (!$has_city && count($formatted_parts_check) < 3) {
                                // Cari kota dari location_name atau state
                                if (!empty($location_name) && $location_name !== 'Unknown') {
                                    $location_name_parts = explode(',', $location_name);
                                    foreach ($location_name_parts as $name_part) {
                                        $name_part = trim($name_part);
                                        if ((stripos($name_part, 'Yogyakarta') !== false || 
                                             stripos($name_part, 'Sleman') !== false) &&
                                            !in_array($name_part, $formatted_parts_check)) {
                                            $formatted .= ', ' . $name_part;
                                            break; // Hanya tambahkan 1, lalu stop
                                        }
                                    }
                                } elseif (!empty($location_details['state']) && stripos($location_details['state'], 'Yogyakarta') !== false) {
                                    if (count($formatted_parts_check) < 3) {
                                        $formatted .= ', Yogyakarta';
                                    }
                                }
                            }
                        }
                        
                        // Final check: pastikan TIDAK lebih dari 3 bagian
                        $final_check = explode(', ', $formatted);
                        if (count($final_check) > 3) {
                            $final = [];
                            if (count($final_check) > 0) $final[] = trim($final_check[0]);
                            if (count($final_check) > 2) {
                                $mid = floor((count($final_check) - 1) / 2);
                                if ($mid > 0 && $mid < count($final_check) - 1) {
                                    $final[] = trim($final_check[$mid]);
                                } elseif (count($final_check) > 1) {
                                    $final[] = trim($final_check[1]);
                                }
                            }
                            if (count($final_check) > 1) $final[] = trim(end($final_check));
                            $formatted = implode(', ', $final);
                        }
                        
                        return $formatted;
                    }
                }
                
                // Fallback: use display_name from Nominatim if available
                if (!empty($location_details['display_name'])) {
                    // Extract first few parts of display_name (HANYA 3 bagian: Desa, Kecamatan, Kota)
                    $display_parts = explode(',', $location_details['display_name']);
                    if (count($display_parts) >= 2) {
                        // Prioritize village/subdistrict/city parts
                        $relevant_parts = [];
                        foreach ($display_parts as $part) {
                            $part = trim($part);
                            // Skip if it's a country or province besar (too general)
                            if (stripos($part, 'Indonesia') === false && 
                                stripos($part, 'Jawa Tengah') === false &&
                                stripos($part, 'Jawa Barat') === false &&
                                stripos($part, 'Jawa Timur') === false &&
                                stripos($part, 'Sumatera') === false &&
                                stripos($part, 'Kalimantan') === false &&
                                stripos($part, 'Sulawesi') === false &&
                                stripos($part, 'Papua') === false &&
                                stripos($part, 'Bali') === false &&
                                stripos($part, 'Nusa Tenggara') === false) {
                                $relevant_parts[] = $part;
                                // HANYA ambil 3 bagian pertama: Desa, Kecamatan, Kota
                                if (count($relevant_parts) >= 3) break;
                            }
                        }
                        if (!empty($relevant_parts)) {
                            // Pastikan hanya 3 bagian
                            $relevant_parts = array_slice($relevant_parts, 0, 3);
                            return implode(', ', $relevant_parts);
                        }
                        // If no relevant parts found, use first 3 parts only
                        return implode(', ', array_slice($display_parts, 0, 3));
                    }
                    // Jika display_name terlalu panjang, ambil hanya 3 bagian pertama
                    $display_parts = explode(',', $location_details['display_name']);
                    return implode(', ', array_slice($display_parts, 0, 3));
                }
            }
        }
        
        // Final fallback: return weather_data name (usually includes city)
        return $location_name;
    }

    public function clearCache($location = null) {
        if ($location) {
            $cache_file = $this->getCacheFile($location);
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
        } else {
            $files = glob($this->cache_dir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
}

