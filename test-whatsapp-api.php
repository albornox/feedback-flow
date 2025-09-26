<?php
// test-whatsapp-api.php - Test completo de la API de WhatsApp

echo "<h1>📱 Test de API WhatsApp - Feedback Flow</h1>";
echo "<style>
    .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 10px; margin: 5px 0; border-radius: 5px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; }
    button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
</style>";

// 1. Verificar configuración
echo "<h2>1. ⚙️ Verificando configuración...</h2>";
try {
    require_once 'config.php';
    
    echo "<div class='info'>";
    echo "<strong>Configuración actual:</strong><br>";
    echo "WHATSAPP_PHONE_NUMBER_ID: <code>" . WHATSAPP_PHONE_NUMBER_ID . "</code><br>";
    echo "WHATSAPP_ACCESS_TOKEN: <code>" . (WHATSAPP_ACCESS_TOKEN ? substr(WHATSAPP_ACCESS_TOKEN, 0, 20) . '...' : 'NO CONFIGURADO') . "</code><br>";
    echo "</div>";
    
    if (empty(WHATSAPP_PHONE_NUMBER_ID)) {
        echo "<div class='error'>❌ WHATSAPP_PHONE_NUMBER_ID está vacío</div>";
        exit;
    }
    
    if (empty(WHATSAPP_ACCESS_TOKEN)) {
        echo "<div class='error'>❌ WHATSAPP_ACCESS_TOKEN está vacío</div>";
        exit;
    }
    
    echo "<div class='success'>✅ Configuración parece correcta</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error cargando config: " . $e->getMessage() . "</div>";
    exit;
}

// 2. Test de la API de WhatsApp (solo verificación, no envío real)
echo "<h2>2. 🔍 Test de conexión a la API...</h2>";

$phoneId = WHATSAPP_PHONE_NUMBER_ID;
$token = WHATSAPP_ACCESS_TOKEN;
$testPhone = "+573001234567"; // Número de prueba para test
$testMessage = "🧪 Mensaje de prueba desde Feedback Flow - " . date('H:i:s');

// URL de la API
$apiUrl = "https://graph.facebook.com/v18.0/$phoneId/messages";

echo "<div class='info'>";
echo "<strong>URL de la API:</strong> <code>$apiUrl</code><br>";
echo "<strong>Teléfono de prueba:</strong> <code>$testPhone</code><br>";
echo "</div>";

// Preparar datos
$data = [
    'messaging_product' => 'whatsapp',
    'to' => $testPhone,
    'type' => 'text',
    'text' => ['body' => $testMessage]
];

$headers = [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
];

echo "<div class='info'>";
echo "<strong>Datos a enviar:</strong><br>";
echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
echo "</div>";

// 3. Hacer la llamada a la API
echo "<h2>3. 📤 Enviando mensaje de prueba...</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<div class='info'>";
echo "<strong>HTTP Code:</strong> $httpCode<br>";
echo "<strong>cURL Error:</strong> " . ($curlError ? $curlError : 'Ninguno') . "<br>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>Respuesta de la API:</strong><br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
echo "</div>";

// 4. Analizar respuesta
echo "<h2>4. 📊 Análisis de la respuesta...</h2>";

if ($httpCode == 200) {
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['messages'])) {
        echo "<div class='success'>✅ Mensaje enviado exitosamente</div>";
        echo "<div class='info'>Message ID: " . $responseData['messages'][0]['id'] . "</div>";
    } else {
        echo "<div class='warning'>⚠️ Respuesta 200 pero formato inesperado</div>";
    }
} else {
    echo "<div class='error'>❌ Error en el envío (HTTP: $httpCode)</div>";
    
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo "<div class='error'>";
        echo "<strong>Error de la API:</strong><br>";
        echo "Código: " . $errorData['error']['code'] . "<br>";
        echo "Mensaje: " . $errorData['error']['message'] . "<br>";
        if (isset($errorData['error']['error_subcode'])) {
            echo "Subcódigo: " . $errorData['error']['error_subcode'] . "<br>";
        }
        echo "</div>";
    }
}

// 5. Test del webhook actual
echo "<h2>5. 🔧 Verificando función de envío del webhook...</h2>";

// Simular la función del webhook
function testWebhookSend($phone, $message) {
    $phoneId = WHATSAPP_PHONE_NUMBER_ID;
    $token = WHATSAPP_ACCESS_TOKEN;
    
    $url = "https://graph.facebook.com/v18.0/$phoneId/messages";
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'text',
        'text' => ['body' => $message]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'response' => $response];
}

$testResult = testWebhookSend("+573001234567", "Test desde función webhook - " . date('H:i:s'));

echo "<div class='info'>";
echo "<strong>Test de función webhook:</strong><br>";
echo "HTTP Code: " . $testResult['code'] . "<br>";
echo "Response: " . htmlspecialchars($testResult['response']) . "<br>";
echo "</div>";

// 6. Recomendaciones
echo "<h2>6. 💡 Recomendaciones</h2>";

if ($httpCode == 200) {
    echo "<div class='success'>";
    echo "<h3>✅ API funcionando correctamente</h3>";
    echo "El problema puede ser:<br>";
    echo "1. La función de envío en el webhook no se está ejecutando<br>";
    echo "2. Hay un error en el flujo lógico del webhook<br>";
    echo "3. El webhook está guardando pero no enviando respuestas<br>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<h3>🔧 Próximos pasos:</h3>";
    echo "1. Revisar los logs del webhook para ver si intenta enviar<br>";
    echo "2. Verificar que la función sendWhatsAppMessage se ejecute<br>";
    echo "3. Agregar más logging al webhook<br>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Problema con la API</h3>";
    echo "Necesitas:<br>";
    echo "1. Verificar que el WHATSAPP_ACCESS_TOKEN es válido<br>";
    echo "2. Confirmar que el WHATSAPP_PHONE_NUMBER_ID es correcto<br>";
    echo "3. Revisar los permisos de la aplicación en Meta<br>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<h3>🧪 Test manual:</h3>";
echo "Si la API funciona arriba, envía otro 'Hola' al WhatsApp y Ana debería responder.<br>";
echo "Si no responde, el problema está en el código del webhook.<br>";
echo "</div>";

echo "<p><strong>🕐 Test completado:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
