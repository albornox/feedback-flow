<?php
// test_database.php - Diagnosticar conexión a base de datos
echo "<h2>🔍 Diagnóstico de Base de Datos</h2>";

// 1. Verificar archivo .env
echo "<h3>1. Verificar archivo .env</h3>";
if (file_exists('.env')) {
    echo "✅ Archivo .env existe<br>";
    
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    
    $dbVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
    foreach ($dbVars as $var) {
        $found = false;
        foreach ($lines as $line) {
            if (strpos($line, $var . '=') === 0) {
                $value = substr($line, strlen($var) + 1);
                echo "✅ $var = " . (strlen($value) > 0 ? 'configurado' : 'VACÍO') . "<br>";
                $found = true;
                break;
            }
        }
        if (!found) {
            echo "❌ $var no encontrado<br>";
        }
    }
} else {
    echo "❌ Archivo .env NO EXISTE<br>";
    echo "<strong>Problema:</strong> Falta el archivo de configuración<br>";
}

// 2. Verificar config.php
echo "<h3>2. Verificar config.php</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php existe<br>";
    
    try {
        require_once 'config.php';
        echo "✅ config.php se carga sin errores<br>";
        
        // Verificar variables de entorno cargadas
        $dbHost = $_ENV['DB_HOST'] ?? 'NO DEFINIDO';
        $dbName = $_ENV['DB_NAME'] ?? 'NO DEFINIDO';
        $dbUser = $_ENV['DB_USER'] ?? 'NO DEFINIDO';
        $dbPassword = $_ENV['DB_PASSWORD'] ?? 'NO DEFINIDO';
        
        echo "📊 Variables cargadas:<br>";
        echo "- DB_HOST: $dbHost<br>";
        echo "- DB_NAME: $dbName<br>";
        echo "- DB_USER: $dbUser<br>";
        echo "- DB_PASSWORD: " . (strlen($dbPassword) > 0 ? 'configurado' : 'VACÍO') . "<br>";
        
    } catch (Exception $e) {
        echo "❌ Error al cargar config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config.php NO EXISTE<br>";
}

// 3. Probar conexión directa
echo "<h3>3. Prueba de Conexión Directa</h3>";

// Credenciales comunes de Cloudways
$testConnections = [
    [
        'host' => 'localhost',
        'name' => 'feedback_flow',
        'user' => 'feedback_flow',
        'pass' => ''
    ],
    [
        'host' => '127.0.0.1',
        'name' => 'pryerancpa_feedback_flow',
        'user' => 'pryerancpa_feedback',
        'pass' => ''
    ]
];

foreach ($testConnections as $i => $conn) {
    echo "<h4>Prueba " . ($i + 1) . ":</h4>";
    try {
        $dsn = "mysql:host={$conn['host']};dbname={$conn['name']}";
        $pdo = new PDO($dsn, $conn['user'], $conn['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Conexión exitosa con:<br>";
        echo "- Host: {$conn['host']}<br>";
        echo "- Database: {$conn['name']}<br>";
        echo "- User: {$conn['user']}<br>";
        
        // Probar consulta
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        echo "- Base de datos actual: " . $result['db_name'] . "<br>";
        
        break;
        
    } catch (Exception $e) {
        echo "❌ Falló: " . $e->getMessage() . "<br>";
    }
}

// 4. Información del servidor
echo "<h3>4. Información del Servidor</h3>";
echo "- PHP Version: " . PHP_VERSION . "<br>";
echo "- Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "- Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// 5. Archivos importantes
echo "<h3>5. Archivos del Proyecto</h3>";
$importantFiles = ['config.php', 'database.php', '.env', 'webhook.php'];
foreach ($importantFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file (" . filesize($file) . " bytes)<br>";
    } else {
        echo "❌ $file NO EXISTE<br>";
    }
}

echo "<hr>";
echo "<h3>💡 Próximos Pasos</h3>";
echo "<p>Basado en los resultados anteriores, necesitamos:</p>";
echo "<ol>";
echo "<li>Configurar correctamente las credenciales de base de datos</li>";
echo "<li>Verificar que la base de datos existe en el servidor</li>";
echo "<li>Ajustar la conexión según el proveedor de hosting</li>";
echo "</ol>";
?>
