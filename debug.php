<?php
// debug.php - Diagnóstico de configuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico de Feedback Flow</h1>";

echo "<h2>1. Verificación de archivos:</h2>";
$files = ['.env', 'config.php', 'index.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file existe</p>";
        if ($file === '.env') {
            echo "<pre>" . htmlspecialchars(file_get_contents($file)) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ $file NO existe</p>";
    }
}

echo "<h2>2. Intentar cargar config.php:</h2>";
try {
    include 'config.php';
    echo "<p style='color: green;'>✅ config.php se cargó sin errores</p>";
    
    echo "<h2>3. Verificar constantes:</h2>";
    $constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'CLAUDE_API_KEY'];
    foreach ($constants as $const) {
        if (defined($const)) {
            $value = constant($const);
            $masked = $const === 'DB_PASS' || $const === 'CLAUDE_API_KEY' ? 
                     (empty($value) ? 'VACÍO' : '***CONFIGURADO***') : $value;
            echo "<p style='color: green;'>✅ $const: $masked</p>";
        } else {
            echo "<p style='color: red;'>❌ $const: NO DEFINIDA</p>";
        }
    }
    
    echo "<h2>4. Probar conexión BD:</h2>";
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error BD: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error cargando config.php: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Variables de entorno:</h2>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";
?>
