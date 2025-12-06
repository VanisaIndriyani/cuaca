<?php
require_once __DIR__ . '/../../config/config.php';

class WeatherData {
    private $conn;
    private $table = 'weather_data';

    public $id;
    public $location;
    public $latitude;
    public $longitude;
    public $temperature;
    public $feels_like;
    public $humidity;
    public $pressure;
    public $wind_speed;
    public $wind_direction;
    public $condition;
    public $description;
    public $icon;
    public $uv_index;
    public $visibility;
    public $recorded_at;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (location, latitude, longitude, temperature, feels_like, humidity, pressure, 
                   wind_speed, wind_direction, `condition`, description, icon, uv_index, visibility, recorded_at, created_at) 
                  VALUES (:location, :latitude, :longitude, :temperature, :feels_like, :humidity, :pressure, 
                          :wind_speed, :wind_direction, :condition, :description, :icon, :uv_index, :visibility, :recorded_at, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':temperature', $this->temperature);
        $stmt->bindParam(':feels_like', $this->feels_like);
        $stmt->bindParam(':humidity', $this->humidity);
        $stmt->bindParam(':pressure', $this->pressure);
        $stmt->bindParam(':wind_speed', $this->wind_speed);
        $stmt->bindParam(':wind_direction', $this->wind_direction);
        $stmt->bindParam(':condition', $this->condition);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':icon', $this->icon);
        $stmt->bindParam(':uv_index', $this->uv_index);
        $stmt->bindParam(':visibility', $this->visibility);
        $stmt->bindParam(':recorded_at', $this->recorded_at);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getLatest($location = null) {
        if ($location) {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE location = :location 
                      ORDER BY recorded_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':location', $location);
        } else {
            $query = "SELECT * FROM " . $this->table . " 
                      ORDER BY recorded_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
        }
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getByDateRange($location, $start_date, $end_date) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE location = :location 
                  AND DATE(recorded_at) BETWEEN :start_date AND :end_date 
                  ORDER BY recorded_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAll($limit = 100) {
        $query = "SELECT * FROM " . $this->table . " 
                  ORDER BY recorded_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAverageByWeek($location) {
        $query = "SELECT 
                    DATE(recorded_at) as date,
                    AVG(temperature) as avg_temp,
                    AVG(humidity) as avg_humidity,
                    AVG(wind_speed) as avg_wind
                  FROM " . $this->table . " 
                  WHERE location = :location 
                  AND recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY DATE(recorded_at)
                  ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':location', $location);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET location = :location, latitude = :latitude, longitude = :longitude, 
                      temperature = :temperature, feels_like = :feels_like, humidity = :humidity, 
                      pressure = :pressure, wind_speed = :wind_speed, wind_direction = :wind_direction, 
                      `condition` = :condition, description = :description, icon = :icon, 
                      uv_index = :uv_index, visibility = :visibility, recorded_at = :recorded_at 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':temperature', $this->temperature);
        $stmt->bindParam(':feels_like', $this->feels_like);
        $stmt->bindParam(':humidity', $this->humidity);
        $stmt->bindParam(':pressure', $this->pressure);
        $stmt->bindParam(':wind_speed', $this->wind_speed);
        $stmt->bindParam(':wind_direction', $this->wind_direction);
        $stmt->bindParam(':condition', $this->condition);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':icon', $this->icon);
        $stmt->bindParam(':uv_index', $this->uv_index);
        $stmt->bindParam(':visibility', $this->visibility);
        $stmt->bindParam(':recorded_at', $this->recorded_at);
        
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}

