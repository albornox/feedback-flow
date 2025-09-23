<?php
// webhook.php - Endpoint principal para recibir mensajes de WhatsApp

require_once 'config.php';
require_once 'database.php';
require_once 'claude.php';
require_once 'whatsapp.php';

// Verificación del webhook (solo se ejecuta una vez durante setup)
if ($_GET['hub_mode'] == 'subscribe' && $_GET['hub_verify_token'] == VERIFY_TOKEN) {
    echo $_GET['hub_challenge'];
    exit;
}

// Procesar mensajes entrantes
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log para debugging
    error_log("Webhook received: " . $input);
    
    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $phone = $message['from'];
        $messageText = $message['text']['body'] ?? '';
        
        // Inicializar clases
        $db = new Database();
        $claude = new ClaudeAI();
        $whatsapp = new WhatsApp();
        
        // Obtener sesión del usuario
        $userSession = $db->getUserSession($phone);
        
        // Procesar mensaje con Claude
        $response = $claude->processMessage($phone, $messageText, $userSession);
        
        // Enviar respuesta
        $whatsapp->sendMessage($phone, $response);
        
        // Responder 200 OK a WhatsApp
        http_response_code(200);
        echo "OK";
    }
} else {
    http_response_code(405);
    echo "Method not allowed";
}
?>
