<?php
require_once 'config.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "OK";
    exit;
}

echo "Webhook ready";
?>
