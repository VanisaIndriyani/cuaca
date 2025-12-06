<?php
require_once __DIR__ . '/../../config/config.php';

class Notification {
    private $conn;
    private $table = 'notifications';

    public $id;
    public $user_id;
    public $title;
    public $message;
    public $type;
    public $status;
    public $sent_at;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, message, type, status, created_at) 
                  VALUES (:user_id, :title, :message, :type, :status, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':status', $this->status);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function markAsSent($id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'sent', sent_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function markAsFailed($id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'failed' 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getByUserId($user_id, $limit = 50) {
        try {
            // Prioritize unread notifications first, then show all notifications
            // This ensures old unread notifications are not hidden
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE user_id = :user_id 
                      ORDER BY 
                          CASE WHEN read_at IS NULL THEN 0 ELSE 1 END ASC,
                          created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                error_log("getByUserId prepare error: " . print_r($error, true));
                // Fallback to simple query
                return $this->getByUserIdSimple($user_id, $limit);
            }
            
            $user_id_int = (int)$user_id;
            $limit_int = (int)$limit;
            
            $stmt->bindParam(':user_id', $user_id_int, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit_int, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                error_log("getByUserId execute error: " . print_r($error, true));
                // Fallback to simple query
                return $this->getByUserIdSimple($user_id, $limit);
            }
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (Exception $e) {
            error_log("Error in getByUserId: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            // Fallback to simple query
            return $this->getByUserIdSimple($user_id, $limit);
        } catch (Error $e) {
            error_log("Fatal error in getByUserId: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            // Fallback to simple query
            return $this->getByUserIdSimple($user_id, $limit);
        }
    }
    
    /**
     * Simple fallback method without CASE statement
     */
    private function getByUserIdSimple($user_id, $limit = 50) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE user_id = :user_id 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("getByUserIdSimple prepare failed");
                return [];
            }
            
            $user_id_int = (int)$user_id;
            $limit_int = (int)$limit;
            
            $stmt->bindParam(':user_id', $user_id_int, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit_int, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                error_log("getByUserIdSimple execute failed");
                return [];
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error in getByUserIdSimple: " . $e->getMessage());
            return [];
        }
    }

    public function getAll($limit = 100) {
        $query = "SELECT n.*, u.name as user_name 
                  FROM " . $this->table . " n 
                  LEFT JOIN users u ON n.user_id = u.id 
                  ORDER BY n.created_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT n.*, u.name as user_name 
                  FROM " . $this->table . " n 
                  LEFT JOIN users u ON n.user_id = u.id 
                  WHERE n.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET user_id = :user_id, title = :title, message = :message, 
                      type = :type, status = :status 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':message', $this->message);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':status', $this->status);
        
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}

