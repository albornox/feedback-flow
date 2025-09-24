<?php
echo "<h1>Verificación de .env</h1>";

echo "<h2>Directorio actual:</h2>";
echo "<p>" . __DIR__ . "</p>";

echo "<h2>Archivos en el directorio:</h2>";
$files = scandir('.');
foreach($files as $file) {
    if($file !== '.' && $file !== '..') {
        echo "<p>📁 $file</p>";
    }
}

echo "<h2>Buscar .env específicamente:</h2>";
if (file_exists('.env')) {
    echo "<p style='color: green;'>✅ .env EXISTE</p>";
    echo "<h3>Contenido:</h3>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('.env'));
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ .env NO EXISTE en este directorio</p>";
}

echo "<h2>Verificar ruta absoluta:</h2>";
$full_path = __DIR__ . '/.env';
echo "<p>Ruta completa: $full_path</p>";
if (file_exists($full_path)) {
    echo "<p style='color: green;'>✅ Archivo encontrado en ruta absoluta</p>";
} else {
    echo "<p style='color: red;'>❌ Archivo NO encontrado en ruta absoluta</p>";
}
?>
