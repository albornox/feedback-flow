<?php
// test_db_connection.php - Prueba directa de conexión sin dependencias
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Prueba Directa de Conexión a Base de Datos</h2>";

// Credenciales directas (basadas en tu screenshot)
$host = 'localhost';
$dbname = 'pryerancpq';
$username = 'pryerancpq';
$password = 'CGq6TvgUU3';

echo "<h3>1. Credenciales que vamos a probar:</h3>";
echo "Host: $host<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "Password: " . str_repeat('*', strlen($password)) . "<br>";

echo "<h3>2. Probando conexión...</h3>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ <strong>Conexión exitosa!</strong><br>";
    
    // Probar una consulta simple
    $stmt = $pdo->query("SELECT DATABASE() as current_db, NOW() as current_time");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Base de datos actual: " . $result['current_db'] . "<br>";
    echo "Hora del servidor: " . $result['current_time'] . "<br>";
    
    // Verificar si existen tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>3. Tablas existentes:</h3>";
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "📋 $table<br>";
        }
    } else {
        echo "❌ No hay tablas en la base de datos<br>";
        echo "<strong>Necesitamos crear las tablas para Feedback Flow</strong><br>";
    }
    
    // Crear tablas directamente aquí
    echo "<h3>4. Creando tablas necesarias...</h3>";
    
    // Tabla conversations
    $createConversations = "
    CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20) NOT NULL UNIQUE,
        customer_name VARCHAR(100) DEFAULT NULL,
        invoice_number VARCHAR(50) DEFAULT NULL,
        attribution_source VARCHAR(100) DEFAULT NULL,
        rating INT DEFAULT NULL,
        feedback_text TEXT DEFAULT NULL,
        status ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
        last_message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($createConversations);
    echo "✅ Tabla 'conversations' creada<br>";
    
    // Tabla messages
    $createMessages = "
    CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        message TEXT NOT NULL,
        direction ENUM('sent', 'received') NOT NULL,
        message_type ENUM('text', 'image', 'document') DEFAULT 'text',
        whatsapp_message_id VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($createMessages);
    echo "✅ Tabla 'messages' creada<br>";
    
    // Insertar datos de prueba
    $insertTest = "
    INSERT IGNORE INTO conversations (phone_number, customer_name, attribution_source, rating, status, last_message)
    VALUES 
    ('+573001234567', 'Cliente Prueba', 'Instagram', 5, 'completed', 'Excelente servicio!'),
    ('+573007654321', 'Usuario Demo', 'Google Maps', 4, 'completed', 'Muy buena comida')
    ";
    
    $pdo->exec($insertTest);
    echo "✅ Datos de prueba insertados<br>";
    
    echo "<h3>✅ Base de datos configurada correctamente!</h3>";
    echo "<p>Ahora puedes:</p>";
    echo "<ul>";
    echo "<li>📱 Enviar mensajes de WhatsApp para probar</li>";
    echo "<li>📊 Ver datos en el dashboard</li>";
    echo "<li>🤖 Procesar conversaciones con IA</li>";
    echo "</ul>";
    
    echo "<p><a href='dashboard-admin/' target='_blank' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Ver Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Error de conexión:</strong><br>";
    echo "Código de error: " . $e->getCode() . "<br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    
    echo "<h3>Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verificar que las credenciales sean correctas</li>";
    echo "<li>Confirmar que la base de datos existe</li>";
    echo "<li>Verificar que el usuario tenga permisos</li>";
    echo "<li>Contactar soporte de Cloudways si persiste</li>";
    echo "</ul>";
}
?>
