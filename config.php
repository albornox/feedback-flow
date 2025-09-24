<?php
// config.php - Configuración principal

// Base de datos Cloudways
define('DB_HOST', 'localhost');
define('DB_NAME', 'pryerancpq');
define('DB_USER', 'pryerancpq');
define('DB_PASS', '5fMMHr7Kqj'); // Cambiar por el password real

// API Keys
define('CLAUDE_API_KEY', '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'); // De tu cuenta Claude Pro
define('WHATSAPP_TOKEN', 'TU_WHATSAPP_TOKEN_AQUI'); // De Meta Developer
define('WHATSAPP_PHONE_ID', 'TU_PHONE_ID_AQUI'); // De WhatsApp Business API
define('VERIFY_TOKEN', 'feedback_flow_2025'); // Token para verificar webhook

// URLs
define('BASE_URL', 'https://phpstack-683796-5878370.cloudwaysapps.com');
define('WEBHOOK_URL', BASE_URL . '/webhook.php');

// Configuración del restaurante
define('RESTAURANT_NAME', 'Nia Bakery');
define('GOOGLE_REVIEWS_URL', 'https://g.page/r/Ccyj3CwKxUzqEAE/review');
define('DISCOUNT_PERCENTAGE', '15%');
define('MANAGER_WHATSAPP', '+57_NUMERO_DEL_GERENTE'); // Para alertas

// Timezone
date_default_timezone_set('America/Bogota');
?>
