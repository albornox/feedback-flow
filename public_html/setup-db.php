<?php
// setup-db.php - Script para crear las tablas necesarias
// EJECUTAR SOLO UNA VEZ

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tabla principal de conversaciones
    $sql1 = "CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20) NOT NULL,
        customer_name VARCHAR(100),
        restaurant VARCHAR(100),
        receipt_number VARCHAR(50),
        source VARCHAR(200),
        rating INT,
        comment TEXT,
        action_taken VARCHAR(100),
        created_at DATETIME,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    // Tabla de sesiones de usuario
    $sql2 = "CREATE TABLE IF NOT EXISTS user_sessions (
        phone_number VARCHAR(20) PRIMARY KEY,
        current_step VARCHAR(50),
        session_data JSON,
        updated_at DATETIME
    )";
    
    $pdo->exec($sql1);
    $pdo->exec($sql2);
    
    echo "✅ Base de datos configurada correctamente!<br>";
    echo "✅ Tabla 'conversations' creada<br>";
    echo "✅ Tabla 'user_sessions' creada<br>";
    echo "<br><strong>¡Ya puedes eliminar este archivo por seguridad!</strong>";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
