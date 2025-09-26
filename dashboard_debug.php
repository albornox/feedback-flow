<?php
// dashboard_debug.php - Verificar qué datos ve el dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug del Dashboard</h2>";

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=pryerancpq;charset=utf8mb4",
        "pryerancpq",
        "CGq6TvgUU3"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado a la base de datos<br><br>";
    
    // 1. Verificar todas las tablas
    echo "<h3>1. Tablas disponibles:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "📋 $table<br>";
    }
    
    // 2. Verificar estructura de conversations
    echo "<h3>2. Estructura de 'conversations':</h3>";
    $stmt = $pdo->query("DESCRIBE conversations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table><br>";
    
    // 3. Contar registros en conversations
    echo "<h3>3. Datos en 'conversations':</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM conversations");
    $total = $stmt->fetch()['total'];
    echo "Total registros: <strong>$total</strong><br>";
    
    if ($total > 0) {
        echo "<h4>Últimos registros:</h4>";
        $stmt = $pdo->query("SELECT * FROM conversations ORDER BY id DESC LIMIT 5");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Teléfono</th><th>Cliente</th><th>Último Mensaje</th><th>Estado</th><th>Creado</th>";
        echo "</tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . ($row['id'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['phone_number'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['customer_name'] ?? 'N/A') . "</td>";
            echo "<td>" . (substr($row['last_message'] ?? 'N/A', 0, 30)) . "...</td>";
            echo "<td>" . ($row['status'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['created_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Verificar messages
    echo "<h3>4. Datos en 'messages':</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM messages");
    $totalMessages = $stmt->fetch()['total'];
    echo "Total mensajes: <strong>$totalMessages</strong><br>";
    
    if ($totalMessages > 0) {
        echo "<h4>Últimos mensajes:</h4>";
        $stmt = $pdo->query("
            SELECT m.*, c.phone_number 
            FROM messages m 
            JOIN conversations c ON m.conversation_id = c.id 
            ORDER BY m.created_at DESC 
            LIMIT 10
        ");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Teléfono</th><th>Mensaje</th><th>Dirección</th><th>Fecha</th>";
        echo "</tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['phone_number'] . "</td>";
            echo "<td>" . substr($row['message'], 0, 50) . "...</td>";
            echo "<td>" . $row['direction'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. Simular consulta del dashboard
    echo "<h3>5. Simulando consulta del dashboard:</h3>";
    
    // Esta es probablemente la consulta que usa tu dashboard
    $dashboardQuery = "
        SELECT 
            id,
            phone_number,
            customer_name,
            invoice_number,
            attribution_source,
            rating,
            feedback_text,
            status,
            created_at
        FROM conversations 
        ORDER BY created_at DESC 
        LIMIT 50
    ";
    
    try {
        $stmt = $pdo->query($dashboardQuery);
        $dashboardData = $stmt->fetchAll();
        
        echo "Registros encontrados por el dashboard: <strong>" . count($dashboardData) . "</strong><br>";
        
        if (count($dashboardData) > 0) {
            echo "<h4>Datos que ve el dashboard:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>Teléfono</th><th>Cliente</th><th>Factura</th><th>Fuente</th><th>Rating</th><th>Estado</th>";
            echo "</tr>";
            foreach ($dashboardData as $row) {
                echo "<tr>";
                echo "<td>" . ($row['phone_number'] ?? '') . "</td>";
                echo "<td>" . ($row['customer_name'] ?? '') . "</td>";
                echo "<td>" . ($row['invoice_number'] ?? '') . "</td>";
                echo "<td>" . ($row['attribution_source'] ?? '') . "</td>";
                echo "<td>" . ($row['rating'] ?? '') . "</td>";
                echo "<td>" . ($row['status'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en consulta del dashboard: " . $e->getMessage() . "<br>";
        echo "Esto explica por qué el dashboard no muestra datos<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}

echo "<hr>";
echo "<h3>Próximos pasos:</h3>";
echo "<ol>";
echo "<li>Si hay datos en 'conversations' pero el dashboard no los ve → problema en el código del dashboard</li>";
echo "<li>Si no hay datos en 'conversations' → problema en el webhook</li>";
echo "<li>Si hay error en la consulta del dashboard → necesitamos ajustar las columnas</li>";
echo "</ol>";
?>
