<?php
// database.php - Clase corregida para manejar operaciones de base de datos

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
    
    // ✅ CORREGIDO: Método que funciona con la estructura real
    public function getAllConversations($limit = 100) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id,
                    c.phone_number,
                    c.customer_name,
                    c.last_message,
                    c.status,
                    c.created_at,
                    c.updated_at,
                    '' as receipt_number,
                    '' as source,
                    NULL as rating,
                    '' as comment,
                    '' as action_taken
                FROM conversations c 
                ORDER BY c.updated_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conversaciones: " . $e->getMessage());
            return [];
        }
    }
    
    // ✅ NUEVO: Obtener conversación por teléfono
    public function getConversationByPhone($phone) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM conversations WHERE phone_number = ?");
            $stmt->execute([$phone]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conversación: " . $e->getMessage());
            return null;
        }
    }
    
    // ✅ CORREGIDO: Crear/actualizar conversación correctamente
    public function saveOrUpdateConversation($phone, $message) {
        try {
            // Verificar si existe
            $existing = $this->getConversationByPhone($phone);
            
            if ($existing) {
                // Actualizar existente
                $stmt = $this->pdo->prepare("
                    UPDATE conversations 
                    SET last_message = ?, updated_at = NOW() 
                    WHERE phone_number = ?
                ");
                $stmt->execute([$message, $phone]);
                return $existing['id'];
            } else {
                // Crear nueva
                $stmt = $this->pdo->prepare("
                    INSERT INTO conversations (phone_number, customer_name, last_message, status, created_at, updated_at) 
                    VALUES (?, ?, ?, 'active', NOW(), NOW())
                ");
                $stmt->execute([$phone, 'WhatsApp User', $message]);
                return $this->pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Error guardando conversación: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ NUEVO: Guardar mensaje en tabla messages
    public function saveMessage($conversationId, $message, $direction = 'received') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO messages (conversation_id, message, direction, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$conversationId, $message, $direction]);
        } catch (PDOException $e) {
            error_log("Error guardando mensaje: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ NUEVO: Obtener mensajes de una conversación
    public function getMessages($conversationId, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM messages 
                WHERE conversation_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$conversationId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo mensajes: " . $e->getMessage());
            return [];
        }
    }
    
    // ✅ NUEVO: Estadísticas básicas
    public function getStats() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM conversations");
            $total = $stmt->fetch()['total'];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as today FROM conversations WHERE DATE(created_at) = CURDATE()");
            $today = $stmt->fetch()['today'];
            
            return ['total' => $total, 'today' => $today];
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return ['total' => 0, 'today' => 0];
        }
    }
}
?>
