<?php
require_once __DIR__ . '/../../config/config.php';

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $google_id;
    public $avatar;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        $query = "INSERT INTO " . $this->table . " 
                  (name, email, password, role, created_at) 
                  VALUES (:name, :email, :password, :role, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $this->role);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, name, email, password, role, avatar, google_id 
                  FROM " . $this->table . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $this->id = $user['id'];
            $this->name = $user['name'];
            $this->email = $user['email'];
            $this->role = $user['role'];
            $this->avatar = $user['avatar'];
            $this->google_id = $user['google_id'];
            return true;
        }
        return false;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByGoogleId($google_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE google_id = :google_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':google_id', $google_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createFromGoogle($google_data) {
        // Check if user with this email already exists
        $existing = $this->findByEmail($google_data['email']);
        
        if ($existing) {
            // Update existing user with Google ID
            $query = "UPDATE " . $this->table . " 
                      SET google_id = :google_id, 
                          avatar = :avatar,
                          name = :name,
                          updated_at = NOW() 
                      WHERE email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':google_id', $google_data['id']);
            $stmt->bindParam(':avatar', $google_data['picture']);
            $stmt->bindParam(':name', $google_data['name']);
            $stmt->bindParam(':email', $google_data['email']);
            
            if ($stmt->execute()) {
                $this->id = $existing['id'];
                return true;
            }
            return false;
        } else {
            // Create new user
            $query = "INSERT INTO " . $this->table . " 
                      (name, email, google_id, avatar, role, created_at) 
                      VALUES (:name, :email, :google_id, :avatar, :role, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $role = 'user';
            
            $stmt->bindParam(':name', $google_data['name']);
            $stmt->bindParam(':email', $google_data['email']);
            $stmt->bindParam(':google_id', $google_data['id']);
            $stmt->bindParam(':avatar', $google_data['picture']);
            $stmt->bindParam(':role', $role);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        }
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getAll() {
        $query = "SELECT id, name, email, role, created_at FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function update() {
        if (!empty($this->avatar)) {
            $query = "UPDATE " . $this->table . " 
                      SET name = :name, email = :email, avatar = :avatar, updated_at = NOW() 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':avatar', $this->avatar);
            $stmt->bindParam(':id', $this->id);
        } else {
            $query = "UPDATE " . $this->table . " 
                      SET name = :name, email = :email, updated_at = NOW() 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':id', $this->id);
        }
        
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
    
    /**
     * Update password
     */
    public function updatePassword($user_id, $new_password) {
        $query = "UPDATE " . $this->table . " 
                  SET password = :password, updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Save password reset code
     */
    public function saveResetCode($email, $code) {
        // Delete old codes for this email
        $deleteQuery = "DELETE FROM password_resets WHERE email = :email";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $deleteStmt->bindParam(':email', $email);
        $deleteStmt->execute();
        
        // Insert new code (expires in 15 minutes)
        $query = "INSERT INTO password_resets (email, code, expires_at) 
                  VALUES (:email, :code, DATE_ADD(NOW(), INTERVAL 15 MINUTE))";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':code', $code);
        
        return $stmt->execute();
    }
    
    /**
     * Verify reset code
     */
    public function verifyResetCode($email, $code) {
        $query = "SELECT * FROM password_resets 
                  WHERE email = :email 
                  AND code = :code 
                  AND expires_at > NOW() 
                  AND used = 0 
                  ORDER BY created_at DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        if ($result) {
            // Mark code as used
            $updateQuery = "UPDATE password_resets SET used = 1 WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':id', $result['id']);
            $updateStmt->execute();
            
            return true;
        }
        
        return false;
    }
}

