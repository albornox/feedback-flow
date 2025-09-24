<?php
require_once 'config.php';
require_once 'database.php';

// Verificación GET (ya funciona)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $expected_token = $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? 'feedback_flow_2025';
    $hub_verify_token = $_GET['hub_verify_token'] ?? '';
    $hub_challenge = $_GET['hub_challenge'] ?? '';
    
    if ($hub_verify_token === $expected_token) {
        echo $hub_challenge;
        exit;
    }
    
    http_response_code(403);
    echo 'Token verification failed';
    exit;
}

// Procesar mensajes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log para debugging
    error_log("Webhook POST received: " . $input);
    
    // Verificar estructura del mensaje
    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $phone = $message['from'];
        $messageText = $message['text']['body'] ?? '';
        $messageId = $message['id'] ?? '';
        
        // Conectar a base de datos
        try {
            $db = new Database();
            
            // Guardar conversación
            $db->saveConversation(
                $phone,
                '', // invoice_number (vacío por ahora)
                '', // attribution_source (vacío por ahora)
                0,  // rating (0 por ahora)
                $messageText, // comment
                'pending' // action
            );
            
            error_log("Message saved: $messageText from $phone");
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // Responder 200 OK a WhatsApp
    http_response_code(200);
    echo "OK";
    exit;
}

// Otros métodos
http_response_code(405);
echo "Method not allowed";
?>
