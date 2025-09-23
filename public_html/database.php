<?php
// database.php - Conexión y funciones de base de datos

require_once 'config.php';

class Database {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function saveConversation($data) {
        $sql = "INSERT INTO conversations (phone_number, customer_name, restaurant, receipt_number, source, rating, comment, action_taken, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['phone'],
            $data['name'] ?? '',
            $data['restaurant'] ?? RESTAURANT_NAME,
            $data['receipt'] ?? '',
            $data['source'] ?? '',
            $data['rating'] ?? null,
            $data['comment'] ?? '',
            $data['action'] ?? ''
        ]);
    }
    
    public function updateConversation($phone, $field, $value) {
        $sql = "UPDATE conversations SET $field = ?, updated_at = NOW() 
                WHERE phone_number = ? ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$value, $phone]);
    }
    
    public function getLastConversation($phone) {
        $sql = "SELECT * FROM conversations WHERE phone_number = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllConversations($limit = 50) {
        $sql = "SELECT * FROM conversations ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function saveUserSession($phone, $step, $data = []) {
        $sql = "INSERT INTO user_sessions (phone_number, current_step, session_data, updated_at) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                current_step = VALUES(current_step), 
                session_data = VALUES(session_data), 
                updated_at = NOW()";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$phone, $step, json_encode($data)]);
    }
    
    public function getUserSession($phone) {
        $sql = "SELECT * FROM user_sessions WHERE phone_number = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$phone]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result['session_data'] = json_decode($result['session_data'], true);
        }
        
        return $result;
    }
}
?>
