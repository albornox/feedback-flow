<?php
require_once 'config.php';

// Verificación GET con parámetros correctos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $expected_token = $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? 'feedback_flow_2025';
    $mode = $_GET['hub_mode'] ?? '';           // Con punto, no guión bajo
    $token = $_GET['hub_verify_token'] ?? '';  // Con punto, no guión bajo  
    $challenge = $_GET['hub_challenge'] ?? ''; // Con punto, no guión bajo
    
    if ($mode === 'subscribe' && $token === $expected_token) {
        echo $challenge;
        exit;
    }
    
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// POST para mensajes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    file_put_contents('webhook-debug.log', date('Y-m-d H:i:s') . "\n" . $input . "\n---\n", FILE_APPEND);
    echo "OK";
    exit;
}

echo "Method not allowed";
?>
