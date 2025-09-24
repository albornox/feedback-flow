<?php
// webhook.php - Endpoint principal para recibir mensajes de WhatsApp
require_once 'config.php';
require_once 'database.php';
require_once 'claude.php';
require_once 'whatsapp.php';

// Verificación del webhook (solo GET requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hub_mode = $_GET['hub_mode'] ?? '';
    $hub_verify_token = $_GET['hub_verify_token'] ?? '';
    $hub_challenge = $_GET['hub_challenge'] ?? '';
    
    // Token que configuraste en Meta (cámbialo si es necesario)
    $verify_token = 'test123';
    
    if ($hub_mode === 'subscribe' && $hub_verify_token === $verify_token) {
        echo $hub_challenge;
        exit;
    } else {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// Procesar mensajes entrantes (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    exit;
}

// Otros métodos no permitidos
http_response_code(405);
echo "Method not allowed";
?>
