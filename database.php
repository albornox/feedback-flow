<?php
// database.php - Clase para manejar operaciones de base de datos
class Database {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public function getAllConversations($limit = 100) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, phone_number, customer_name, restaurant, receipt_number, source, rating, comment, action_taken, created_at, updated_at, last_message, status FROM conversations ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            logMessage("Error obteniendo conversaciones: " . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    public function getConversation($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM conversations WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            logMessage("Error obteniendo conversación: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    public function saveConversation($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO conversations 
                (phone_number, customer_name, restaurant, receipt_number, source, rating, comment, action_taken, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $data['phone_number'],
                $data['customer_name'] ?? null,
                $data['restaurant'] ?? RESTAURANT_NAME,
                $data['receipt_number'] ?? null,
                $data['source'] ?? null,
                $data['rating'] ?? null,
                $data['comment'] ?? null,
                $data['action_taken'] ?? null
            ]);
        } catch (PDOException $e) {
            logMessage("Error guardando conversación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function updateConversation($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE conversations 
                SET customer_name = ?, receipt_number = ?, source = ?, rating = ?, comment = ?, action_taken = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $data['customer_name'] ?? null,
                $data['receipt_number'] ?? null,
                $data['source'] ?? null,
                $data['rating'] ?? null,
                $data['comment'] ?? null,
                $data['action_taken'] ?? null,
                $id
            ]);
        } catch (PDOException $e) {
            logMessage("Error actualizando conversación: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function getUserSession($phone_number) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_sessions WHERE phone_number = ?");
            $stmt->execute([$phone_number]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['session_data']) {
                $result['session_data'] = json_decode($result['session_data'], true);
            }
            
            return $result;
        } catch (PDOException $e) {
            logMessage("Error obteniendo sesión: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    public function saveUserSession($phone_number, $step, $data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions (phone_number, current_step, session_data, updated_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                current_step = VALUES(current_step), 
                session_data = VALUES(session_data), 
                updated_at = VALUES(updated_at)
            ");
            
            return $stmt->execute([
                $phone_number,
                $step,
                json_encode($data)
            ]);
        } catch (PDOException $e) {
            logMessage("Error guardando sesión: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function deleteUserSession($phone_number) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE phone_number = ?");
            return $stmt->execute([$phone_number]);
        } catch (PDOException $e) {
            logMessage("Error eliminando sesión: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
?>
