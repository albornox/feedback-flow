<?php
// webhook.php - Version más permisiva para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log completo de la request
$debug_info = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'get_params' => $_GET,
    'post_data' => file_get_contents('php://input'),
    'headers' => getallheaders(),
    'timestamp' => date('Y-m-d H:i:s')
];
file_put_contents('webhook-debug.log', json_encode($debug_info, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);

// Para requests GET (verificación)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Si no hay parámetros de verificación, mostrar lo que recibimos
    if (empty($_GET)) {
        echo "No GET parameters received";
        exit;
    }
    
    $hub_mode = $_GET['hub_mode'] ?? '';
    $hub_verify_token = $_GET['hub_verify_token'] ?? '';
    $hub_challenge = $_GET['hub_challenge'] ?? '';
    
    // Verificación estándar
    if ($hub_mode === 'subscribe' && $hub_verify_token === 'test123') {
        echo $hub_challenge;
        exit;
    }
    
    // Si falla verificación, mostrar qué recibimos
    echo "Verification attempt failed\n";
    echo "Mode: " . ($hub_mode ?: 'EMPTY') . "\n";
    echo "Token: " . ($hub_verify_token ?: 'EMPTY') . "\n";
    echo "Challenge: " . ($hub_challenge ?: 'EMPTY') . "\n";
    exit;
}

// Para requests POST (mensajes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST received";
    exit;
}

echo "Method not allowed";
?>
