<?php
// simple_env_check.php - Verificación paso a paso sin errores fatales
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Verificación Simple de Configuración</h2>";

// 1. Mostrar contenido del .env
echo "<h3>1. Contenido del archivo .env</h3>";
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($envContent);
    echo "</pre>";
} else {
    echo "❌ Archivo .env no existe<br>";
}

// 2. Verificar si config.php existe y su contenido
echo "<h3>2. Verificar config.php</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php existe (" . filesize('config.php') . " bytes)<br>";
    
    // Mostrar primeras líneas de config.php
    $configContent = file_get_contents('config.php');
    $lines = explode("\n", $configContent);
    $firstLines = array_slice($lines, 0, 10);
    
    echo "<strong>Primeras 10 líneas de config.php:</strong><br>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    foreach ($firstLines as $i => $line) {
        echo ($i + 1) . ": " . htmlspecialchars($line) . "\n";
    }
    echo "</pre>";
} else {
    echo "❌ config.php no existe<br>";
}

// 3. Información del servidor
echo "<h3>3. Información del Servidor</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'No disponible') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No disponible') . "<br>";

// 4. Verificar extensiones de PHP necesarias
echo "<h3>4. Extensiones de PHP</h3>";
$extensions = ['pdo', 'pdo_mysql', 'curl', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext<br>";
    } else {
        echo "❌ $ext (FALTANTE)<br>";
    }
}

// 5. Archivos del proyecto
echo "<h3>5. Archivos del Proyecto</h3>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..' && !is_dir($file)) {
        $size = filesize($file);
        echo "📄 $file ($size bytes)<br>";
    }
}

echo "<hr>";
echo "<h3>Datos para configurar la base de datos:</h3>";
echo "<p>Para Cloudways, las credenciales típicas son:</p>";
echo "<ul>";
echo "<li><strong>DB_HOST:</strong> localhost</li>";
echo "<li><strong>DB_NAME:</strong> [tu_usuario]_[nombre_app]</li>";
echo "<li><strong>DB_USER:</strong> [tu_usuario]_[nombre_user]</li>";
echo "<li><strong>DB_PASSWORD:</strong> [contraseña que configuraste]</li>";
echo "</ul>";

echo "<p>Busca estas credenciales en tu panel de Cloudways → Application Management → Access Details</p>";
?>
