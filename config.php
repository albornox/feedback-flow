<?php
// config.php - Configuración principal de Feedback Flow
// Configuración segura usando variables de entorno

// Cargar variables del archivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Cargar archivo .env
loadEnv(__DIR__ . '/.env');

// Función helper para obtener variables de entorno
function env($key, $default = null) {
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

// === CONFIGURACIÓN BASE DE DATOS ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'pryerancpq');
define('DB_USER', 'pryerancpq');
define('DB_PASS', env('DB_PASSWORD')); // Desde .env

// === APIS EXTERNAS ===
define('CLAUDE_API_KEY', env('CLAUDE_API_KEY')); // Desde .env

// === WHATSAPP BUSINESS API ===
define('WHATSAPP_ACCESS_TOKEN', env('WHATSAPP_ACCESS_TOKEN', ''));
define('WHATSAPP_PHONE_NUMBER_ID', env('WHATSAPP_PHONE_NUMBER_ID', ''));
define('WHATSAPP_WEBHOOK_VERIFY_TOKEN', env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'feedback_flow_2025'));

// === CONFIGURACIÓN RESTAURANTE ===
define('RESTAURANT_NAME', 'Nia Bakery');
define('GOOGLE_REVIEWS_URL', env('GOOGLE_REVIEWS_URL')); // Desde .env

// === CONFIGURACIÓN SISTEMA ===
define('TIMEZONE', 'America/Bogota');
define('LANGUAGE', 'es');
define('DEBUG_MODE', true);

// === MENSAJES DEFAULT ===
define('WELCOME_MESSAGE', '¡Hola! 👋 Soy el asistente de ' . RESTAURANT_NAME . '. ¿Podrías ayudarme con algunas preguntas rápidas sobre tu experiencia?');

define('POSITIVE_FEEDBACK_MESSAGE', '¡Qué alegría saber que tuviste una buena experiencia! 😊 ¿Te gustaría dejar una reseña en Google? Te regalamos un 10% de descuento en tu próxima visita 🎁');

define('NEGATIVE_FEEDBACK_MESSAGE', 'Lamento mucho que no hayas tenido una buena experiencia. 😔 He enviado tus comentarios directamente a nuestro gerente para mejorar. ¡Gracias por tu honestidad!');

// === CONFIGURACIÓN TIMEZONE ===
date_default_timezone_set(TIMEZONE);

// === FUNCIÓN DE LOGGING ===
function logMessage($message, $type = 'INFO') {
    if (DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] [$type] $message");
    }
}

// Verificar configuración crítica
function checkConfig() {
    $required = [
        'DB_PASS' => env('DB_PASSWORD'),
        'CLAUDE_API_KEY' => env('CLAUDE_API_KEY'),
        'GOOGLE_REVIEWS_URL' => env('GOOGLE_REVIEWS_URL')
    ];
    
    foreach ($required as $key => $value) {
        if (empty($value)) {
            throw new Exception("Configuración faltante: $key");
        }
    }
    
    return true;
}
?>
