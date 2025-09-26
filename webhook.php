<?php
// webhook.php - Versión mejorada y segura
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'database.php';

// Log function
function logActivity($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents('webhook_logs.txt', "[$timestamp] $message\n", FILE_APPEND);
}

// Verificación GET (para Meta)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    logActivity("GET verification - Mode: $mode, Token: $token");
    
    if ($mode === 'subscribe' && $token === WHATSAPP_WEBHOOK_VERIFY_TOKEN) {
        logActivity("Webhook verification successful");
        echo $challenge;
        exit;
    }
    
    logActivity("Webhook verification failed");
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// POST para mensajes entrantes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    logActivity("POST received: " . substr($input, 0, 200) . "...");
    
    try {
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['entry'])) {
            logActivity("Invalid webhook data structure");
            echo "OK";
            exit;
        }
        
        // Inicializar base de datos
        $db = new Database();
        
        // Procesar mensajes
        foreach ($data['entry'] as $entry) {
            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    if (isset($change['value']['messages'])) {
                        foreach ($change['value']['messages'] as $message) {
                            processIncomingMessage($message, $db);
                        }
                    }
                }
            }
        }
        
        logActivity("Processing completed successfully");
        
    } catch (Exception $e) {
        logActivity("Error processing: " . $e->getMessage());
    }
    
    echo "OK";
    exit;
}

function processIncomingMessage($message, $db) {
    try {
        $phone = $message['from'] ?? '';
        $messageText = $message['text']['body'] ?? '';
        $messageId = $message['id'] ?? '';
        
        logActivity("Processing message from $phone: $messageText");
        
        // Limpiar número de teléfono
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        // Guardar conversación usando el método corregido
        $conversationId = $db->saveOrUpdateConversation($phone, $messageText);
        
        if ($conversationId) {
            // Guardar mensaje recibido
            $db->saveMessage($conversationId, $messageText, 'received');
            
            logActivity("Message saved with conversation ID: $conversationId");
            
            // Generar y enviar respuesta
            $response = generateSmartResponse($messageText, $phone, $db);
            if (sendWhatsAppMessage($phone, $response)) {
                // Guardar respuesta enviada
                $db->saveMessage($conversationId, $response, 'sent');
                logActivity("Response sent and saved: " . substr($response, 0, 50) . "...");
            }
        }
        
    } catch (Exception $e) {
        logActivity("Error in processIncomingMessage: " . $e->getMessage());
    }
}

function generateSmartResponse($message, $phone, $db) {
    $message = strtolower(trim($message));
    
    // Obtener historial de conversación para respuestas contextuales
    $conversation = $db->getConversationByPhone($phone);
    $messages = $conversation ? $db->getMessages($conversation['id'], 5) : [];
    
    // Lógica de respuestas mejorada
    if (strpos($message, 'hola') !== false || strpos($message, 'hi') !== false) {
        return "¡Hola! 👋 Soy Ana de " . RESTAURANT_NAME . ". ¿Podrías ayudarme con 2 minutitos para mejorar tu experiencia? Envíame el número de factura que está resaltado en tu cuenta.";
    } 
    
    if (preg_match('/\d{3,}/', $message)) {
        return "¡Perfecto! Gracias por el número de factura. 🙏\n\n¿Cómo te enteraste de " . RESTAURANT_NAME . "?\n\nA) Google/búsqueda en internet\nB) Instagram o redes sociales\nC) Un amigo te recomendó\nD) Pasabas por la zona\nE) Otro (¿cuál?)";
    }
    
    if (preg_match('/[abcde]/i', $message) || strpos($message, 'google') !== false || strpos($message, 'instagram') !== false) {
        return "Súper, gracias por el dato 📊\n\nÚltima pregunta rápida: Del 1 al 5, ¿qué tal estuvo todo hoy en " . RESTAURANT_NAME . "?\n\n1️⃣ = Muy malo 😞\n2️⃣ = Malo 😕\n3️⃣ = Regular 😐\n4️⃣ = Bueno 😊\n5️⃣ = Excelente 😍";
    }
    
    if (preg_match('/[4-5]/', $message)) {
        return "¡Qué alegría saber que la pasaste súper bien! 🎉\n\n¿Nos ayudarías con una reseña rápida en Google? Te toma 30 segundos:\n\n" . GOOGLE_REVIEWS_URL . "\n\nComo agradecimiento: tienes 10% de descuento en tu próxima visita 🎁\n\n¿Te parece?";
    }
    
    if (preg_match('/[1-3]/', $message)) {
        // Enviar alerta al gerente
        sendManagerAlert($phone, $message);
        return "Oh no... lamento mucho que no haya sido una buena experiencia 😔\n\nMe interesa muchísimo saber qué pasó para poder mejorar. ¿Podrías contarme qué salió mal?\n\nPierre (el dueño) va a querer saber de esto para solucionarlo personalmente.";
    }
    
    // Respuesta por defecto
    return "Gracias por tu mensaje. Un miembro de nuestro equipo te responderá pronto. 😊\n\nSi quieres dejar feedback sobre tu visita, puedes enviarme el número de tu factura.";
}

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
        
        logActivity("WhatsApp API Response: HTTP $httpCode");
        return $httpCode === 200;
        
    } catch (Exception $e) {
        logActivity("Error sending WhatsApp: " . $e->getMessage());
        return false;
    }
}

function sendManagerAlert($phone, $complaint) {
    // Log crítico para el gerente
    $alert = "🚨 FEEDBACK NEGATIVO RECIBIDO 🚨\n";
    $alert .= "Teléfono: $phone\n";
    $alert .= "Mensaje: $complaint\n";
    $alert .= "Hora: " . date('Y-m-d H:i:s') . "\n";
    
    logActivity("MANAGER_ALERT: $alert");
    
    // Aquí podrías enviar email, SMS o notificación push al gerente
    // sendEmail(MANAGER_EMAIL, "Feedback Negativo - " . RESTAURANT_NAME, $alert);
}

// Respuesta por defecto para métodos no permitidos
http_response_code(405);
echo "Method not allowed";
?>
