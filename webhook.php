<?php
// webhook.php - Con debugging avanzado
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log todo lo que recibe
$debug_info = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'get_params' => $_GET,
    'timestamp' => date('Y-m-d H:i:s')
];
file_put_contents('webhook-debug.log', json_encode($debug_info) . "\n", FILE_APPEND);

// Verificación del webhook (solo GET requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hub_mode = $_GET['hub_mode'] ?? 'NOT_SET';
    $hub_verify_token = $_GET['hub_verify_token'] ?? 'NOT_SET';
    $hub_challenge = $_GET['hub_challenge'] ?? 'NOT_SET';
    
    // Token que configuraste en Meta
    $verify_token = 'test123';
    
    // Debug output
    echo "Hub Mode: $hub_mode<br>";
    echo "Verify Token Received: $hub_verify_token<br>";
    echo "Expected Token: $verify_token<br>";
    echo "Challenge: $hub_challenge<br>";
    
    if ($hub_mode === 'subscribe' && $hub_verify_token === $verify_token) {
        echo "VERIFICATION SUCCESS: $hub_challenge";
        exit;
    } else {
        echo "VERIFICATION FAILED";
        exit;
    }
}

// POST requests para mensajes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST received - OK";
    exit;
}

echo "Method not allowed";
?>
