<?php
// webhook.php - Versión final que evita el error de last_message
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log function
function logActivity($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents('webhook_logs.txt', "[$timestamp] $message\n", FILE_APPEND);
}

// Cargar variables de entorno
try {
    if (file_exists('.env')) {
        $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
    }
} catch (Exception $e) {
    logActivity("Error loading .env: " . $e->getMessage());
}

// Verificación GET (para Meta)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $expected_token = $_ENV['WEBHOOK_VERIFY_TOKEN'] ?? 'feedback_flow_2025';
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    logActivity("GET verification - Mode: $mode, Token: $token");
    
    if ($mode === 'subscribe' && $token === $expected_token) {
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
        
        // Procesar mensajes
        foreach ($data['entry'] as $entry) {
            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    if (isset($change['value']['messages'])) {
                        foreach ($change['value']['messages'] as $message) {
                            processIncomingMessage($message);
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

function processIncomingMessage($message) {
    try {
        $phone = $message['from'] ?? '';
        $messageText = $message['text']['body'] ?? '';
        $messageId = $message['id'] ?? '';
        
        logActivity("Processing message from $phone: $messageText");
        
        // Limpiar número de teléfono
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        // Guardar en base de datos usando estructura verificada
        $conversationId = saveConversationFixed($phone, $messageText);
        
        if ($conversationId) {
            logActivity("Message saved with ID: $conversationId");
            
            // Generar y enviar respuesta
            $response = generateSimpleResponse($messageText);
            if (sendWhatsAppMessageSimple($phone, $response)) {
                logActivity("Response sent: " . substr($response, 0, 50) . "...");
                
                // Guardar respuesta también
                saveMessageOnly($conversationId, $response, 'sent');
            }
        }
        
    } catch (Exception $e) {
        logActivity("Error in processIncomingMessage: " . $e->getMessage());
    }
}

function saveConversationFixed($phone, $message) {
    try {
        // Conexión directa
        $pdo = new PDO(
            "mysql:host=localhost;dbname=pryerancpq;charset=utf8mb4",
            "pryerancpq",
            $_ENV['DB_PASSWORD'] ?? 'CGq6TvgUU3'
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar qué columnas existen realmente
        $stmt = $pdo->query("DESCRIBE conversations");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        logActivity("Available columns: " . implode(', ', $columns));
        
        // Construir query dinámicamente basado en columnas disponibles
        if (in_array('last_message', $columns)) {
            // Usar estructura completa
            $stmt = $pdo->prepare("
                INSERT INTO conversations (phone_number, last_message, status, created_at, updated_at) 
                VALUES (?, ?, 'active', NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                last_message = VALUES(last_message), 
                updated_at = NOW()
            ");
            $stmt->execute([$phone, $message]);
            logActivity("Using full structure with last_message");
        } else {
            // Usar estructura básica
            $stmt = $pdo->prepare("
                INSERT INTO conversations (phone_number, customer_name, created_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                customer_name = VALUES(customer_name)
            ");
            $stmt->execute([$phone, 'WhatsApp User']);
            logActivity("Using basic structure without last_message");
        }
        
        // Obtener ID
        $stmt = $pdo->prepare("SELECT id FROM conversations WHERE phone_number = ?");
        $stmt->execute([$phone]);
        $result = $stmt->fetch();
        
        if ($result) {
            $conversationId = $result['id'];
            
            // Guardar mensaje en tabla messages
            saveMessageOnly($conversationId, $message, 'received');
            
            return $conversationId;
        }
        
        return false;
        
    } catch (Exception $e) {
        logActivity("Database error: " . $e->getMessage());
        return false;
    }
}

function saveMessageOnly($conversationId, $message, $direction) {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=pryerancpq;charset=utf8mb4",
            "pryerancpq",
            $_ENV['DB_PASSWORD'] ?? 'CGq6TvgUU3'
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, message, direction, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$conversationId, $message, $direction]);
        
        logActivity("Message saved in messages table: $direction");
        return true;
        
    } catch (Exception $e) {
        logActivity("Error saving message: " . $e->getMessage());
        return false;
    }
}

function generateSimpleResponse($message) {
    $message = strtolower(trim($message));
    
    // Respuestas dinámicas
    if (strpos($message, 'prueba') !== false) {
        return "¡Hola! Veo que estás probando el sistema. Todo está funcionando correctamente. ¿Podrías enviarme el número de tu factura para continuar?";
    } elseif (preg_match('/\d+/', $message)) {
        return "¡Perfecto! Gracias por el número de factura. ¿Cómo te enteraste de Nia Bakery?\n\nA) Google\nB) Instagram\nC) Un amigo\nD) Pasabas por la zona";
    } elseif (strpos($message, 'a') !== false || strpos($message, 'google') !== false) {
        return "Genial. Del 1 al 5, ¿qué tal estuvo todo hoy?\n\n1️⃣ = Muy malo\n2️⃣ = Malo\n3️⃣ = Regular\n4️⃣ = Bueno\n5️⃣ = Excelente";
    } elseif (preg_match('/[4-5]/', $message)) {
        return "¡Qué alegría! 🎉 ¿Nos ayudarías con una reseña en Google? Como agradecimiento: 10% de descuento en tu próxima visita 🎁";
    } elseif (preg_match('/[1-3]/', $message)) {
        return "Lamento mucho eso 😔 ¿Podrías contarme qué pasó? Pierre (el dueño) quiere saber para mejorar.";
    } else {
        return "¡Hola! 👋 Soy Ana de Nia Bakery. ¿Podrías ayudarme con 2 minutitos para mejorar tu experiencia? Envíame el número de factura que está resaltado en tu cuenta.";
    }
}

function sendWhatsAppMessageSimple($phone, $message) {
    try {
        $phoneNumberId = $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? "84931711825594";
        $accessToken = $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? "EAAJzCXQp00sBPjcl3Qm5XJUxofxVYwfZBvNiZCJTosiT1ZBlTH7Jc5tlyX3Kxr87znswTZBQL";
        
        $url = "https://graph.facebook.com/v18.0/$phoneNumberId/messages";
        
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
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        logActivity("WhatsApp API: HTTP $httpCode");
        return $httpCode === 200;
        
    } catch (Exception $e) {
        logActivity("Error sending WhatsApp: " . $e->getMessage());
        return false;
    }
}

// Respuesta por defecto
http_response_code(405);
echo "Method not allowed";
?>
