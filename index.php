<?php
// Diagnóstico temporal - ELIMINAR después
echo "<h1>Diagnóstico .env</h1>";
echo "<p>Directorio: " . __DIR__ . "</p>";
echo "<p>¿Existe .env?: " . (file_exists('.env') ? 'SÍ' : 'NO') . "</p>";
if (file_exists('.env')) {
    echo "<pre>" . htmlspecialchars(file_get_contents('.env')) . "</pre>";
}
echo "<hr>";
// Fin diagnóstico
echo "Feedback Flow funcionando!";
echo "<br><a href='/dashboard-admin/'>Dashboard</a>";
echo "<br><a href='/setup-db.php'>Setup BD</a>";
?>
