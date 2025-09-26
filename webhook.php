<?php
require_once 'config.php';
require_once 'database.php';
require_once 'whatsapp.php';
require_once 'claude.php';

// Log function para debugging
function logActivity($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents('webhook_logs.txt', "[$timestamp] $message\n", FILE_APPEND);
}

// Verificación GET (para Meta)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $expected_token = $_ENV['WEBHOOK_VERIFY_TOKEN'] ?? 'feedback_flow_2025';
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    logActivity("GET request - Mode: $mode, Token: $token, Challenge: $challenge");
    
    if ($mode === 'subscribe' && $token === $expected_token) {
        logActivity("Webhook verification successful");
        echo $challenge;
        exit;
    }
    
    logActivity("Webhook verification failed");
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// POST para mensajes entrantes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    logActivity("POST request received: " . $input);
    
    // Verificar estructura del mensaje
    if (!isset($data['entry']) || !is_array($data['entry'])) {
        logActivity("Invalid webhook data structure");
        echo "OK";
        exit;
    }
    
    // Procesar cada entrada
    foreach ($data['entry'] as $entry) {
        if (!isset($entry['changes'])) continue;
        
        foreach ($entry['changes'] as $change) {
            if (!isset($change['value']['messages'])) continue;
            
            foreach ($change['value']['messages'] as $message) {
                processIncomingMessage($message);
            }
        }
    }
    
    echo "OK";
    exit;
}

function processIncomingMessage($message) {
    try {
        // Extraer datos del mensaje
        $phone = $message['from'] ?? '';
        $messageText = $message['text']['body'] ?? '';
        $messageId = $message['id'] ?? '';
        $timestamp = $message['timestamp'] ?? time();
        
        logActivity("Processing message from $phone: $messageText");
        
        // Limpiar número de teléfono (remover prefijo de WhatsApp si existe)
        $phone = preg_replace('/^(\+|00)/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        // Guardar mensaje en base de datos
        $conversationId = saveConversation($phone, $messageText, 'received');
        
        if ($conversationId) {
            logActivity("Message saved to database with ID: $conversationId");
            
            // Procesar con Claude AI
            $response = processWithClaude($phone, $messageText, $conversationId);
            
            if ($response) {
                // Enviar respuesta por WhatsApp
                $sent = sendWhatsAppMessage($phone, $response);
                
                if ($sent) {
                    // Guardar respuesta en base de datos
                    saveConversation($phone, $response, 'sent', $conversationId);
                    logActivity("Response sent and saved: " . substr($response, 0, 50) . "...");
                } else {
                    logActivity("Failed to send WhatsApp message");
                }
            } else {
                logActivity("No response generated from Claude");
            }
        } else {
            logActivity("Failed to save message to database");
        }
        
    } catch (Exception $e) {
        logActivity("Error processing message: " . $e->getMessage());
    }
}

function saveConversation($phone, $message, $direction, $conversationId = null) {
    try {
        $pdo = getDatabaseConnection();
        
        if ($conversationId) {
            // Actualizar conversación existente
            $stmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, message, direction, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$conversationId, $message, $direction]);
            return $conversationId;
        } else {
            // Crear nueva conversación
            $stmt = $pdo->prepare("
                INSERT INTO conversations (phone_number, last_message, status, created_at, updated_at) 
                VALUES (?, ?, 'active', NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                last_message = VALUES(last_message), 
                updated_at = NOW()
            ");
            $stmt->execute([$phone, $message]);
            
            // Obtener ID de la conversación
            $stmt = $pdo->prepare("SELECT id FROM conversations WHERE phone_number = ? ORDER BY updated_at DESC LIMIT 1");
            $stmt->execute([$phone]);
            $result = $stmt->fetch();
            
            if ($result) {
                $conversationId = $result['id'];
                
                // Guardar el mensaje
                $stmt = $pdo->prepare("
                    INSERT INTO messages (conversation_id, message, direction, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$conversationId, $message, $direction]);
                
                return $conversationId;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        logActivity("Database error: " . $e->getMessage());
        return false;
    }
}

// Para otros métodos HTTP
http_response_code(405);
echo "Method not allowed";
?>
