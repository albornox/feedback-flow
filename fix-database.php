<?php
// fix-database.php - Script para corregir la estructura de la base de datos

echo "<h1>🔧 Reparación de Base de Datos - Feedback Flow</h1>";
echo "<style>
    .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .info { background: #d1ecf1; color: #0c5460; padding: 10px; margin: 5px 0; border-radius: 5px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// 1. Conectar a la base de datos
echo "<h2>1. 🔌 Conectando a la base de datos...</h2>";
try {
    require_once 'config.php';
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Conexión exitosa</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error de conexión: " . $e->getMessage() . "</div>";
    exit;
}

// 2. Verificar estructura actual
echo "<h2>2. 🔍 Verificando estructura actual de 'conversations'...</h2>";
try {
    $stmt = $pdo->query("DESCRIBE conversations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<strong>Columnas actuales:</strong><br>";
    echo "<table>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Verificar si last_message existe
    $hasLastMessage = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'last_message') {
            $hasLastMessage = true;
            break;
        }
    }
    
    if ($hasLastMessage) {
        echo "<div class='success'>✅ La columna 'last_message' existe</div>";
    } else {
        echo "<div class='error'>❌ La columna 'last_message' NO existe - necesita ser agregada</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error verificando estructura: " . $e->getMessage() . "</div>";
}

// 3. Agregar columnas faltantes
echo "<h2>3. 🔧 Agregando columnas faltantes...</h2>";

$requiredColumns = [
    'last_message' => 'TEXT NULL',
    'status' => 'VARCHAR(50) DEFAULT "active"',
    'receipt_number' => 'VARCHAR(100) NULL',
    'source' => 'VARCHAR(100) NULL',
    'rating' => 'INT(1) NULL',
    'comment' => 'TEXT NULL',
    'action_taken' => 'VARCHAR(100) NULL'
];

foreach ($requiredColumns as $columnName => $columnDef) {
    try {
        // Verificar si la columna existe
        $stmt = $pdo->query("SHOW COLUMNS FROM conversations LIKE '$columnName'");
        $exists = $stmt->fetch();
        
        if (!$exists) {
            // Agregar la columna
            $sql = "ALTER TABLE conversations ADD COLUMN $columnName $columnDef";
            $pdo->exec($sql);
            echo "<div class='success'>✅ Columna '$columnName' agregada</div>";
        } else {
            echo "<div class='info'>ℹ️ Columna '$columnName' ya existe</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error agregando '$columnName': " . $e->getMessage() . "</div>";
    }
}

// 4. Verificar tabla messages
echo "<h2>4. 📨 Verificando tabla 'messages'...</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<div class='warning'>⚠️ Tabla 'messages' no existe - creándola...</div>";
        
        $createMessages = "
        CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            message TEXT NOT NULL,
            direction ENUM('received', 'sent') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createMessages);
        echo "<div class='success'>✅ Tabla 'messages' creada</div>";
    } else {
        echo "<div class='success'>✅ Tabla 'messages' existe</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error con tabla messages: " . $e->getMessage() . "</div>";
}

// 5. Verificar estructura final
echo "<h2>5. ✅ Verificación final...</h2>";
try {
    $stmt = $pdo->query("DESCRIBE conversations");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='success'>✅ Estructura final de 'conversations':</div>";
    echo "<div class='info'>" . implode(', ', $finalColumns) . "</div>";
    
    // Test de inserción
    echo "<h3>🧪 Test de inserción...</h3>";
    $testPhone = '+1234567890';
    $testMessage = 'Test message after fix';
    
    $stmt = $pdo->prepare("
        INSERT INTO conversations (phone_number, customer_name, last_message, status, created_at, updated_at) 
        VALUES (?, ?, ?, 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        last_message = VALUES(last_message), 
        updated_at = NOW()
    ");
    
    $result = $stmt->execute([$testPhone, 'Test User', $testMessage]);
    
    if ($result) {
        echo "<div class='success'>✅ Test de inserción exitoso</div>";
    } else {
        echo "<div class='error'>❌ Test de inserción falló</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error en verificación final: " . $e->getMessage() . "</div>";
}

// 6. Instrucciones finales
echo "<h2>6. 🎯 Próximos pasos</h2>";
echo "<div class='success'>";
echo "<h3>Si todo salió bien:</h3>";
echo "1. El webhook ahora debería funcionar correctamente<br>";
echo "2. Envía un mensaje de WhatsApp al número de prueba<br>";
echo "3. Verifica que Ana responde<br>";
echo "4. Revisa el dashboard para ver la conversación<br>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>Si hay errores:</h3>";
echo "1. Revisa los mensajes de error arriba<br>";
echo "2. Contacta para soporte técnico<br>";
echo "3. Comparte los errores específicos<br>";
echo "</div>";

echo "<p><strong>🕐 Reparación completada:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
