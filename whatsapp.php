<?php
// whatsapp.php - Funciones para enviar mensajes por WhatsApp

require_once 'config.php';

class WhatsApp {
    private $token;
    private $phoneId;
    
    public function __construct() {
        $this->token = WHATSAPP_TOKEN;
        $this->phoneId = WHATSAPP_PHONE_ID;
    }
    
    public function sendMessage($to, $message) {
        $url = "https://graph.facebook.com/v18.0/" . $this->phoneId . "/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'text' => ['body' => $message]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log para debugging
        error_log("WhatsApp API Response: " . $response . " | HTTP Code: " . $httpCode);
        
        return $httpCode == 200;
    }
    
    public function sendTemplateMessage($to, $template, $parameters = []) {
        // Para mensajes con template (opcional, más avanzado)
        // Implementar si necesitas templates específicos
    }
}
?>
