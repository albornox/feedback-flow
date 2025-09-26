<?php
// dashboard/index.php - Panel mejorado para ver conversaciones

require_once '../config.php';
require_once '../database.php';

try {
    $db = new Database();
    $conversations = $db->getAllConversations(100);
    $stats = $db->getStats();
} catch (Exception $e) {
    $error = "Error conectando a la base de datos: " . $e->getMessage();
    $conversations = [];
    $stats = ['total' => 0, 'today' => 0];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Flow - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error { background: #ff6b6b; color: white; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success { background: #51cf66; color: white; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .status-active { color: #51cf66; font-weight: bold; }
        .status-inactive { color: #868e96; }
        .message-preview { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .refresh-btn { 
            background: #007bff; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            margin: 10px 0;
        }
        .conversation-row:hover { background-color: #f8f9fa; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Feedback Flow Dashboard</h1>
        <h2><?php echo RESTAURANT_NAME; ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="error">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <button class="refresh-btn" onclick="location.reload()">🔄 Actualizar</button>
        
        <div class="stats">
            <div class="stat-box">
                <h3>Total Conversaciones</h3>
                <p><?php echo $stats['total']; ?></p>
            </div>
            
            <div class="stat-box">
                <h3>Conversaciones Hoy</h3>
                <p><?php echo $stats['today']; ?></p>
            </div>
            
            <div class="stat-box">
                <h3>Estado del Sistema</h3>
                <p><?php echo count($conversations) > 0 ? '✅ Funcionando' : '⚠️ Sin datos'; ?></p>
            </div>
        </div>
        
        <?php if (empty($conversations)): ?>
            <div class="error">
                📭 No hay conversaciones registradas aún.
                <br><br>
                <strong>Para probar:</strong>
                <br>1. Envía un mensaje a tu número de WhatsApp Business
                <br>2. El webhook debería procesarlo automáticamente
                <br>3. Actualiza esta página
            </div>
        <?php else: ?>
            <div class="success">
                ✅ Sistema funcionando correctamente. Se encontraron <?php echo count($conversations); ?> conversaciones.
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Teléfono</th>
                    <th>Cliente</th>
                    <th>Último Mensaje</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($conversations as $conv): ?>
                <tr class="conversation-row" onclick="viewConversation(<?php echo $conv['id']; ?>)">
                    <td><?php echo date('d/m/Y H:i', strtotime($conv['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($conv['phone_number']); ?></td>
                    <td><?php echo htmlspecialchars($conv['customer_name'] ?? 'Usuario WhatsApp'); ?></td>
                    <td class="message-preview"><?php echo htmlspecialchars($conv['last_message'] ?? 'Sin mensajes'); ?></td>
                    <td class="<?php echo 'status-' . ($conv['status'] ?? 'inactive'); ?>">
                        <?php echo ucfirst($conv['status'] ?? 'inactive'); ?>
                    </td>
                    <td>
                        <button onclick="event.stopPropagation(); viewConversation(<?php echo $conv['id']; ?>)" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                            👁️ Ver
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3>ℹ️ Información del Sistema</h3>
            <p><strong>Última actualización:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            <p><strong>Base de datos:</strong> <?php echo DB_NAME; ?></p>
            <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_NAME']; ?></p>
        </div>
    </div>
    
    <script>
        function viewConversation(id) {
            alert('Función de vista detallada pendiente de implementar para conversación #' + id);
            // Aquí puedes implementar una vista detallada o redireccionar
        }
        
        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
