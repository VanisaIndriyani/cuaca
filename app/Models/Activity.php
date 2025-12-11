<?php
require_once __DIR__ . '/../../config/config.php';

class Activity {
    private $conn;
    private $table = 'activities';

    public $id;
    public $user_id;
    public $title;
    public $description;
    public $category;
    public $activity_date;
    public $start_time;
    public $end_time;
    public $location;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, description, category, activity_date, start_time, end_time, location, created_at) 
                  VALUES (:user_id, :title, :description, :category, :activity_date, :start_time, :end_time, :location, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':activity_date', $this->activity_date);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':end_time', $this->end_time);
        $stmt->bindParam(':location', $this->location);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read($user_id = null, $date = null) {
        if ($user_id && !isAdmin()) {
            $query = "SELECT a.*, u.name as user_name 
                      FROM " . $this->table . " a 
                      LEFT JOIN users u ON a.user_id = u.id 
                      WHERE a.user_id = :user_id";
            $params = [':user_id' => $user_id];
            
            if ($date) {
                $query .= " AND DATE(a.activity_date) = :date";
                $params[':date'] = $date;
            }
            
            $query .= " ORDER BY a.activity_date DESC, a.start_time ASC";
        } else {
            $query = "SELECT a.*, u.name as user_name 
                      FROM " . $this->table . " a 
                      LEFT JOIN users u ON a.user_id = u.id";
            
            if ($date) {
                $query .= " WHERE DATE(a.activity_date) = :date";
                $params = [':date' => $date];
            } else {
                $params = [];
            }
            
            $query .= " ORDER BY a.activity_date DESC, a.start_time ASC";
        }
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT a.*, u.name as user_name 
                  FROM " . $this->table . " a 
                  LEFT JOIN users u ON a.user_id = u.id 
                  WHERE a.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET title = :title, description = :description, category = :category, 
                      activity_date = :activity_date, start_time = :start_time, 
                      end_time = :end_time, location = :location, updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':activity_date', $this->activity_date);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':end_time', $this->end_time);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function getByCategory($user_id = null) {
        if ($user_id && !isAdmin()) {
            $query = "SELECT category, COUNT(*) as count 
                      FROM " . $this->table . " 
                      WHERE user_id = :user_id 
                      GROUP BY category";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
        } else {
            $query = "SELECT category, COUNT(*) as count 
                      FROM " . $this->table . " 
                      GROUP BY category";
            $stmt = $this->conn->prepare($query);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByDateRange($user_id, $start_date, $end_date) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND activity_date BETWEEN :start_date AND :end_date 
                  ORDER BY activity_date ASC, start_time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getMostUsedLocation($user_id) {
        $query = "SELECT location, COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND location IS NOT NULL 
                  AND location != '' 
                  GROUP BY location 
                  ORDER BY count DESC, location ASC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result ? $result['location'] : null;
    }
}

