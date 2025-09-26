<?php
// webhook-debug.php - Debug detallado para encontrar por qué no responde

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'database.php';

// Simular un mensaje entrante para debug
function debugWebhookFlow() {
    echo "<h1>🔍 Debug Detallado del Webhook</h1>";
    echo "<style>
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; margin: 5px 0; border-radius: 5px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>";
    
    // Simular datos de un mensaje real
    $testPhone = "+573001234567";
    $testMessage = "Hola";
    
    echo "<div class='step'>";
    echo "<h2>Paso 1: Simulando mensaje entrante</h2>";
    echo "<strong>Teléfono:</strong> $testPhone<br>";
    echo "<strong>Mensaje:</strong> $testMessage<br>";
    echo "</div>";
    
    // Paso 1: Inicializar Database
    echo "<div class='step'>";
    echo "<h2>Paso 2: Inicializando base de datos</h2>";
    try {
        $db = new Database();
        echo "<div class='success'>✅ Base de datos inicializada</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error inicializando BD: " . $e->getMessage() . "</div>";
        return;
    }
    echo "</div>";
    
    // Paso 2: Guardar conversación
    echo "<div class='step'>";
    echo "<h2>Paso 3: Guardando conversación</h2>";
    try {
        $conversationId = $db->saveOrUpdateConversation($testPhone, $testMessage);
        if ($conversationId) {
            echo "<div class='success'>✅ Conversación guardada con ID: $conversationId</div>";
            
            // Guardar mensaje recibido
            $messageResult = $db->saveMessage($conversationId, $testMessage, 'received');
            if ($messageResult) {
                echo "<div class='success'>✅ Mensaje guardado en tabla messages</div>";
            } else {
                echo "<div class='error'>❌ Error guardando mensaje</div>";
            }
        } else {
            echo "<div class='error'>❌ Error guardando conversación</div>";
            return;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error en paso de guardado: " . $e->getMessage() . "</div>";
        return;
    }
    echo "</div>";
    
    // Paso 3: Generar respuesta
    echo "<div class='step'>";
    echo "<h2>Paso 4: Generando respuesta</h2>";
    try {
        $response = generateSmartResponse($testMessage, $testPhone, $db);
        echo "<div class='success'>✅ Respuesta generada:</div>";
        echo "<div class='info'><strong>Respuesta:</strong> " . htmlspecialchars($response) . "</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error generando respuesta: " . $e->getMessage() . "</div>";
        return;
    }
    echo "</div>";
    
    // Paso 4: Intentar enviar mensaje
    echo "<div class='step'>";
    echo "<h2>Paso 5: Intentando enviar mensaje</h2>";
    try {
        $sendResult = sendWhatsAppMessage($testPhone, $response);
        if ($sendResult) {
            echo "<div class='success'>✅ Mensaje enviado exitosamente</div>";
            
            // Guardar mensaje enviado
            $saveResult = $db->saveMessage($conversationId, $response, 'sent');
            if ($saveResult) {
                echo "<div class='success'>✅ Respuesta guardada en BD</div>";
            } else {
                echo "<div class='warning'>⚠️ Mensaje enviado pero no guardado en BD</div>";
            }
        } else {
            echo "<div class='error'>❌ Error enviando mensaje</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error en envío: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Verificar logs
    echo "<div class='step'>";
    echo "<h2>Paso 6: Revisando logs recientes</h2>";
    if (file_exists('webhook_logs.txt')) {
        $logs = file_get_contents('webhook_logs.txt');
        $recentLogs = array_slice(explode("\n", $logs), -10);
        echo "<div class='info'>";
        echo "<strong>Últimas 10 líneas del log:</strong><br>";
        echo "<pre>" . htmlspecialchars(implode("\n", $recentLogs)) . "</pre>";
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ No se encontró archivo webhook_logs.txt</div>";
    }
    echo "</div>";
    
    echo "<p><strong>🕐 Debug completado:</strong> " . date('Y-m-d H:i:s') . "</p>";
}

// Función de respuesta (copiada del webhook)
function generateSmartResponse($message, $phone, $db) {
    $message = strtolower(trim($message));
    
    if (strpos($message, 'hola') !== false || strpos($message, 'hi') !== false) {
        return "¡Hola! 👋 Soy Ana de " . RESTAURANT_NAME . ". ¿Podrías ayudarme con 2 minutitos para mejorar tu experiencia? Envíame el número de factura que está resaltado en tu cuenta.";
    } 
    
    if (preg_match('/\d{3,}/', $message)) {
        return "¡Perfecto! Gracias por el número de factura. 🙏\n\n¿Cómo te enteraste de " . RESTAURANT_NAME . "?\n\nA) Google/búsqueda en internet\nB) Instagram o redes sociales\nC) Un amigo te recomendó\nD) Pasabas por la zona\nE) Otro (¿cuál?)";
    }
    
    return "Gracias por tu mensaje. Un miembro de nuestro equipo te responderá pronto. 😊";
}

// Función de envío (copiada del webhook)
function sendWhatsAppMessage($phone, $message) {
    try {
        $url = "https://graph.facebook.com/v18.0/" . WHATSAPP_PHONE_NUMBER_ID . "/messages";
        
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
            "Authorization: Bearer " . WHATSAPP_ACCESS_TOKEN,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<div class='info'>";
        echo "<strong>Detalles del envío:</strong><br>";
        echo "HTTP Code: $httpCode<br>";
        echo "Response: " . htmlspecialchars($response) . "<br>";
        echo "</div>";
        
        return $httpCode === 200;
        
    } catch (Exception $e) {
        echo "<div class='error'>Excepción en sendWhatsAppMessage: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Ejecutar debug
debugWebhookFlow();
?>
