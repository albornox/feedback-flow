<?php
// claude.php - Integración con Claude IA

require_once 'config.php';
require_once 'database.php';

class ClaudeAI {
    private $apiKey;
    private $db;
    
    public function __construct() {
        $this->apiKey = CLAUDE_API_KEY;
        $this->db = new Database();
    }
    
    public function processMessage($phone, $message, $userSession) {
        $step = $userSession['current_step'] ?? 'start';
        $sessionData = $userSession['session_data'] ?? [];
        
        switch($step) {
            case 'start':
                return $this->handleStart($phone, $message);
            case 'language_selected':
                return $this->handleReceiptNumber($phone, $message, $sessionData);
            case 'receipt_collected':
                return $this->handleAttribution($phone, $message, $sessionData);
            case 'attribution_collected':
                return $this->handleRating($phone, $message, $sessionData);
            case 'rating_collected':
                return $this->handleFinalResponse($phone, $message, $sessionData);
            default:
                return $this->handleStart($phone, $message);
        }
    }
    
    private function handleStart($phone, $message) {
        // Detectar idioma o respuesta a selección de idioma
        $message = strtolower(trim($message));
        
        if (strpos($message, 'english') !== false || strpos($message, '🇺🇸') !== false) {
            $language = 'en';
            $response = "Hello! 👋\n\nI saw you visited Nia Bakery today. I'm Ana, the restaurant's digital assistant.\n\nCould you help me with 2 minutes to improve your experience? 😊\n\nTo start, could you send me the receipt number that's highlighted on your bill?";
        } else if (strpos($message, 'español') !== false || strpos($message, '🇪🇸') !== false) {
            $language = 'es';
            $response = "¡Hola! 👋\n\nVi que visitaste Nia Bakery hoy. Soy Ana, la asistente digital del restaurante.\n\n¿Me ayudas con 2 minutitos para mejorar tu experiencia? 😊\n\nPara comenzar, ¿podrías enviarme el número de factura que está resaltado en tu cuenta?";
        } else {
            // Primera interacción - mostrar opciones de idioma
            $language = 'both';
            $response = "Hi! 👋 / ¡Hola! 👋\n\nI'm Ana, Nia Bakery's digital assistant.\nSoy Ana, la asistente digital de Nia Bakery.\n\nCould you help us improve your experience?\n¿Podrías ayudarnos a mejorar tu experiencia?\n\nPlease choose your language / Por favor elige tu idioma:\n🇺🇸 English\n🇪🇸 Español";
        }
        
        // Guardar estado
        if ($language !== 'both') {
            $this->db->saveUserSession($phone, 'language_selected', ['language' => $language]);
        }
        
        return $response;
    }
    
    private function handleReceiptNumber($phone, $message, $sessionData) {
        $language = $sessionData['language'] ?? 'es';
        $receiptNumber = trim($message);
        
        // Guardar número de factura
        $this->db->saveConversation([
            'phone' => $phone,
            'receipt' => $receiptNumber,
            'restaurant' => RESTAURANT_NAME
        ]);
        
        // Actualizar sesión
        $sessionData['receipt'] = $receiptNumber;
        $this->db->saveUserSession($phone, 'receipt_collected', $sessionData);
        
        if ($language === 'en') {
            $response = "Perfect! Thank you 🙏\n\nNow I'd like to know: how did you hear about Nia Bakery?\n\nA) Google/internet search\nB) Instagram or social media\nC) A friend recommended it\nD) You were passing by the area\nE) Other (which one?)";
        } else {
            $response = "¡Perfecto! Gracias 🙏\n\nAhora me gustaría saber: ¿cómo te enteraste de Nia Bakery?\n\nA) Google/búsqueda en internet\nB) Instagram o redes sociales\nC) Un amigo te recomendó\nD) Pasabas por la zona\nE) Otro (¿cuál?)";
        }
        
        return $response;
    }
    
    private function handleAttribution($phone, $message, $sessionData) {
        $language = $sessionData['language'] ?? 'es';
        $source = $this->parseSource($message);
        
        // Actualizar conversación con fuente
        $this->db->updateConversation($phone, 'source', $source);
        
        // Actualizar sesión
        $sessionData['source'] = $source;
        $this->db->saveUserSession($phone, 'attribution_collected', $sessionData);
        
        if ($language === 'en') {
            $response = "Great, thanks for that info 📊\n\nLast quick question: From 1 to 5, how was everything today at Nia Bakery?\n\n1️⃣ = Very bad 😞\n2️⃣ = Bad 😕\n3️⃣ = Regular 😐\n4️⃣ = Good 😊\n5️⃣ = Excellent 😍";
        } else {
            $response = "Súper, gracias por el dato 📊\n\nÚltima pregunta rápida: Del 1 al 5, ¿qué tal estuvo todo hoy en Nia Bakery?\n\n1️⃣ = Muy malo 😞\n2️⃣ = Malo 😕\n3️⃣ = Regular 😐\n4️⃣ = Bueno 😊\n5️⃣ = Excelente 😍";
        }
        
        return $response;
    }
    
    private function handleRating($phone, $message, $sessionData) {
        $language = $sessionData['language'] ?? 'es';
        $rating = $this->parseRating($message);
        
        // Actualizar conversación con rating
        $this->db->updateConversation($phone, 'rating', $rating);
        
        // Actualizar sesión
        $sessionData['rating'] = $rating;
        $this->db->saveUserSession($phone, 'rating_collected', $sessionData);
        
        if ($rating >= 4) {
            // Rating positivo
            if ($language === 'en') {
                $response = "So happy to know you had a great experience! 🎉\n\nWould you help us with a quick Google review? Takes 30 seconds:\n\n" . GOOGLE_REVIEWS_URL . "\n\nAs a thank you: you get " . DISCOUNT_PERCENTAGE . " discount on your next visit 🎁\n\nSound good?";
            } else {
                $response = "¡Qué alegría saber que la pasaste súper bien! 🎉\n\n¿Nos ayudarías con una reseña rápida en Google? Te toma 30 segundos:\n\n" . GOOGLE_REVIEWS_URL . "\n\nComo agradecimiento: tienes " . DISCOUNT_PERCENTAGE . " de descuento en tu próxima visita 🎁\n\n¿Te parece?";
            }
            $action = 'review_requested';
        } else {
            // Rating negativo
            if ($language === 'en') {
                $response = "Oh no... I'm so sorry it wasn't a good experience 😔\n\nI'd really like to know what happened so we can improve. Could you tell me what went wrong?\n\nPierre (the owner) will want to know about this to fix it personally.";
            } else {
                $response = "Oh no... lamento mucho que no haya sido una buena experiencia 😔\n\nMe interesa muchísimo saber qué pasó para poder mejorar. ¿Podrías contarme qué salió mal?\n\nPierre (el dueño) va a querer saber de esto para solucionarlo personalmente.";
            }
            $action = 'complaint_handling';
            
            // Enviar alerta al gerente
            $this->sendManagerAlert($phone, $rating, $sessionData);
        }
        
        // Actualizar acción tomada
        $this->db->updateConversation($phone, 'action_taken', $action);
        
        return $response;
    }
    
    private function parseSource($message) {
        $message = strtolower($message);
        
        if (strpos($message, 'a') !== false || strpos($message, 'google') !== false) {
            return 'Google/Internet Search';
        } else if (strpos($message, 'b') !== false || strpos($message, 'instagram') !== false || strpos($message, 'social') !== false) {
            return 'Instagram/Social Media';
        } else if (strpos($message, 'c') !== false || strpos($message, 'amigo') !== false || strpos($message, 'friend') !== false || strpos($message, 'recomend') !== false) {
            return 'Friend Recommendation';
        } else if (strpos($message, 'd') !== false || strpos($message, 'zona') !== false || strpos($message, 'passing') !== false) {
            return 'Walking by';
        } else {
            return 'Other: ' . $message;
        }
    }
    
    private function parseRating($message) {
        $message = trim($message);
        
        // Buscar números del 1-5
        if (preg_match('/[1-5]/', $message, $matches)) {
            return intval($matches[0]);
        }
        
        return null;
    }
    
    private function sendManagerAlert($phone, $rating, $sessionData) {
        // Aquí puedes implementar envío de alerta por WhatsApp al gerente
        // Por ahora solo guardamos en logs
        error_log("ALERT: Rating " . $rating . " from " . $phone . " at " . date('Y-m-d H:i:s'));
    }
}
?>
