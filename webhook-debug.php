<?php
// webhook-debug.php - Para debugging POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    
    // Guardar en archivo para revisar
    file_put_contents('webhook-post-debug.log', date('Y-m-d H:i:s') . "\n" . $input . "\n---\n", FILE_APPEND);
    
    echo "POST received and logged";
    exit;
}

echo "Webhook debug ready";
?>
