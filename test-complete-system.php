<?php
// test-complete-system.php - Diagnóstico completo del sistema

echo "<h1>🔍 Diagnóstico Completo - Feedback Flow</h1>";
echo "<style>
    .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; margin: 5px 0; border-radius: 5px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// 1. Verificar archivos principales
echo "<h2>1. 📁 Verificando archivos principales...</h2>";
$files = [
    'config.php' => 'Configuración principal',
    'database.php' => 'Clase de base de datos',
    'webhook.php' => 'Webhook de WhatsApp',
    '.env' => 'Variables de entorno',
    'dashboard-admin/index.php' => 'Dashboard principal'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file ($description) - Existe</div>";
    } else {
        echo "<div class='error'>❌ $file ($description) - NO EXISTE</div>";
    }
}

// 2. Verificar configuración
echo "<h2>2. ⚙️ Verificando configuración...</h2>";
try {
    require_once 'config.php';
    echo "<div class='success'>✅ Config.php cargado correctamente</div>";
    
    // Verificar constantes importantes
    $constants = [
        'DB_HOST' => DB_HOST,
        'DB_NAME' => DB_NAME,
        'DB_USER' => DB_USER,
        'RESTAURANT_NAME' => RESTAURANT_NAME,
        'WHATSAPP_ACCESS_TOKEN' => WHATSAPP_ACCESS_TOKEN ? '✅ Configurado' : '❌ Falta',
        'WHATSAPP_PHONE_NUMBER_ID' => WHATSAPP_PHONE_NUMBER_ID ? '✅ Configurado' : '❌ Falta',
        'GOOGLE_REVIEWS_URL' => GOOGLE_REVIEWS_URL ? '✅ Configurado' : '❌ Falta'
    ];
    
    echo "<table>";
    echo "<tr><th>Constante</th><th>Valor</th></tr>";
    foreach ($constants as $name => $value) {
        echo "<tr><td>$name</td><td>$value</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error cargando config: " . $e->getMessage() . "</div>";
}

// 3. Verificar conexión a base de datos
echo "<h2>3. 🗄️ Verificando base de datos...</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Conexión a base de datos exitosa</div>";
    
    // Verificar tablas
    $tables = ['conversations', 'messages'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<div class='success'>✅ Tabla '$table': $count registros</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error con tabla '$table': " . $e->getMessage() . "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error de conexión a BD: " . $e->getMessage() . "</div>";
}

// 4. Probar clase Database
echo "<h2>4. 🔧 Probando clase Database...</h2>";
try {
    require_once 'database.php';
    $db = new Database();
    echo "<div class='success'>✅ Clase Database inicializada</div>";
    
    $conversations = $db->getAllConversations(5);
    echo "<div class='success'>✅ Método getAllConversations(): " . count($conversations) . " conversaciones</div>";
    
    $stats = $db->getStats();
    echo "<div class='success'>✅ Método getStats(): " . $stats['total'] . " total, " . $stats['today'] . " hoy</div>";
    
    if (count($conversations) > 0) {
        echo "<div class='success'>✅ Datos disponibles para el dashboard</div>";
        
        echo "<h3>📋 Últimas conversaciones:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Teléfono</th><th>Último Mensaje</th><th>Fecha</th></tr>";
        foreach (array_slice($conversations, 0, 3) as $conv) {
            echo "<tr>";
            echo "<td>" . $conv['id'] . "</td>";
            echo "<td>" . htmlspecialchars($conv['phone_number']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($conv['last_message'] ?? '', 0, 50)) . "...</td>";
            echo "<td>" . $conv['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No hay conversaciones aún</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error con clase Database: " . $e->getMessage() . "</div>";
}

// 5. Verificar webhook
echo "<h2>5. 🔗 Verificando webhook...</h2>";
$webhookUrl = "https://" . $_SERVER['HTTP_HOST'] . "/webhook.php";
echo "<div class='success'>📍 URL del webhook: <a href='$webhookUrl' target='_blank'>$webhookUrl</a></div>";

// Simular verificación GET
$testUrl = $webhookUrl . "?hub_mode=subscribe&hub_verify_token=" . WHATSAPP_WEBHOOK_VERIFY_TOKEN . "&hub_challenge=test123";
echo "<div class='success'>🧪 Test de verificación: <a href='$testUrl' target='_blank'>Probar webhook</a></div>";

// 6. Verificar dashboard
echo "<h2>6. 📊 Verificando dashboard...</h2>";
$dashboardUrl = "https://" . $_SERVER['HTTP_HOST'] . "/dashboard-admin/";
echo "<div class='success'>📍 URL del dashboard: <a href='$dashboardUrl' target='_blank'>$dashboardUrl</a></div>";

// 7. Información del sistema
echo "<h2>7. ℹ️ Información del sistema</h2>";
echo "<table>";
echo "<tr><th>Parámetro</th><th>Valor</th></tr>";
echo "<tr><td>Fecha actual</td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
echo "<tr><td>Zona horaria</td><td>" . date_default_timezone_get() . "</td></tr>";
echo "<tr><td>Versión PHP</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>Servidor</td><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";
echo "<tr><td>Host</td><td>" . $_SERVER['HTTP_HOST'] . "</td></tr>";
echo "</table>";

// 8. Recomendaciones
echo "<h2>8. 💡 Próximos pasos recomendados</h2>";
echo "<div class='success'>
<h3>✅ Si todo está en verde:</h3>
<ol>
<li>Envía un mensaje de WhatsApp a tu número de negocio</li>
<li>Verifica que aparece en el dashboard</li>
<li>El sistema está funcionando correctamente</li>
</ol>
</div>";

echo "<div class='warning'>
<h3>⚠️ Si hay errores en rojo:</h3>
<ol>
<li>Revisa los archivos que faltan</li>
<li>Verifica las credenciales en .env</li>
<li>Contacta para soporte técnico</li>
</ol>
</div>";

echo "<div class='success'>
<h3>🔄 Para probar el flujo completo:</h3>
<ol>
<li>Envía: 'Hola' → Respuesta de bienvenida</li>
<li>Envía: '12345' → Pregunta sobre cómo conoció el restaurante</li>
<li>Envía: 'A' → Pregunta de calificación</li>
<li>Envía: '5' → Solicitud de reseña y descuento</li>
</ol>
</div>";

echo "<p><strong>🕐 Diagnóstico completado:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
