<?php
// webhook_debug.php - Verificar si el webhook está funcionando
echo "<h2>Diagnóstico del Webhook WhatsApp</h2>";

// 1. Verificar archivos de log
echo "<h3>1. Archivos de Log</h3>";
$logFiles = ['webhook_logs.txt', 'webhook-debug.log'];

foreach ($logFiles as $logFile) {
    echo "<h4>Archivo: $logFile</h4>";
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $logLines = explode("\n", trim($logs));
        $recentLogs = array_slice($logLines, -10); // Últimas 10 líneas
        
        echo "✅ Archivo existe (" . filesize($logFile) . " bytes)<br>";
        echo "📝 Últimas entradas:<br>";
        echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: scroll;'>";
        echo "<pre>";
        foreach ($recentLogs as $log) {
            if (!empty(trim($log))) {
                echo htmlspecialchars($log) . "\n";
            }
        }
        echo "</pre>";
        echo "</div><br>";
    } else {
        echo "❌ Archivo NO EXISTE - El webhook no ha registrado actividad<br><br>";
    }
}

// 2. Probar webhook directamente
echo "<h3>2. Probar Webhook Directamente</h3>";
echo "<form method='post'>";
echo "Simular mensaje de WhatsApp:<br>";
echo "<textarea name='webhook_data' rows='10' cols='80' placeholder='Pega aquí datos de webhook de Meta o usa el ejemplo'>{
  \"entry\": [{
    \"changes\": [{
      \"value\": {
        \"messages\": [{
          \"from\": \"+573001234567\",
          \"text\": {\"body\": \"Hola, soy una prueba\"},
          \"id\": \"test_message_123\",
          \"timestamp\": \"" . time() . "\"
        }]
      }
    }]
  }]
}</textarea><br><br>";
echo "<input type='submit' name='test_webhook' value='Probar Webhook' style='background: #007cba; color: white; padding: 10px; border: none; border-radius: 5px;'>";
echo "</form>";

if (isset($_POST['test_webhook'])) {
    $webhookData = $_POST['webhook_data'];
    
    echo "<div style='margin-top: 15px; padding: 15px; background: #e8f4fd; border-radius: 5px;'>";
    echo "<h4>Resultado de la prueba:</h4>";
    
    // Simular POST al webhook
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://phpstack-683796-5878370.cloudwaysapps.com/webhook.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $webhookData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Código de respuesta: $httpCode<br>";
    echo "Respuesta del webhook: " . htmlspecialchars($response) . "<br>";
    
    if ($httpCode === 200) {
        echo "✅ Webhook respondió correctamente<br>";
    } else {
        echo "❌ Error en el webhook<br>";
    }
    echo "</div>";
}

// 3. Verificar configuración de WhatsApp en Meta
echo "<h3>3. Configuración de Meta</h3>";
echo "<p>Verifica en tu panel de Meta que:</p>";
echo "<ul>";
echo "<li>✅ Webhook URL: <code>https://phpstack-683796-5878370.cloudwaysapps.com/webhook.php</code></li>";
echo "<li>✅ Verify Token: <code>feedback_flow_2025</code></li>";
echo "<li>✅ Estado: Verificado/Activo</li>";
echo "<li>✅ Suscripciones: messages, message_deliveries</li>";
echo "</ul>";

// 4. Verificar base de datos
echo "<h3>4. Estado de la Base de Datos</h3>";
try {
    $host = 'localhost';
    $dbname = 'pryerancpq';
    $username = 'pryerancpq';
    $password = 'CGq6TvgUU3';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM conversations");
    $total = $stmt->fetch()['total'];
    echo "💬 Conversaciones en DB: $total<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM messages");
    $totalMessages = $stmt->fetch()['total'];
    echo "📨 Mensajes en DB: $totalMessages<br>";
    
    if ($total > 0) {
        echo "<h4>Últimas conversaciones:</h4>";
        $stmt = $pdo->query("SELECT phone_number, last_message, created_at FROM conversations ORDER BY updated_at DESC LIMIT 3");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Teléfono</th><th>Último Mensaje</th><th>Fecha</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['last_message'], 0, 30)) . "...</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Error de conexión a DB: " . $e->getMessage();
}

// 5. Enlaces útiles
echo "<h3>5. Enlaces de Diagnóstico</h3>";
echo "<a href='dashboard-admin/' target='_blank'>📊 Dashboard</a> | ";
echo "<a href='/' target='_blank'>📱 Landing Page</a> | ";
echo "<a href='webhook.php' target='_blank'>🔗 Webhook</a>";

echo "<hr>";
echo "<h3>Pasos para solucionar:</h3>";
echo "<ol>";
echo "<li>Si no hay logs: Problema en configuración de Meta</li>";
echo "<li>Si hay logs pero no se guarda en DB: Problema en el código PHP</li>";
echo "<li>Si se guarda en DB pero no aparece en dashboard: Problema en la interfaz</li>";
echo "</ol>";
?>
