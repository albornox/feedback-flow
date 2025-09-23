<?php
// dashboard/index.php - Panel básico para ver conversaciones

require_once '../config.php';
require_once '../database.php';

$db = new Database();
$conversations = $db->getAllConversations(100);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Flow - Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>📊 Feedback Flow Dashboard</h1>
        <h2><?php echo RESTAURANT_NAME; ?></h2>
        
        <div class="stats">
            <div class="stat-box">
                <h3>Total Conversaciones</h3>
                <p><?php echo count($conversations); ?></p>
            </div>
            
            <div class="stat-box">
                <h3>Rating Promedio</h3>
                <?php 
                $ratings = array_filter(array_column($conversations, 'rating'));
                $avgRating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
                ?>
                <p><?php echo $avgRating; ?>/5 ⭐</p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Teléfono</th>
                    <th>Factura</th>
                    <th>Fuente</th>
                    <th>Rating</th>
                    <th>Comentario</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($conversations as $conv): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($conv['created_at'])); ?></td>
                    <td><?php echo $conv['phone_number']; ?></td>
                    <td><?php echo $conv['receipt_number']; ?></td>
                    <td><?php echo $conv['source']; ?></td>
                    <td>
                        <?php if($conv['rating']): ?>
                            <?php echo $conv['rating']; ?>/5
                            <?php if($conv['rating'] >= 4): ?>
                                <span class="positive">😊</span>
                            <?php else: ?>
                                <span class="negative">😔</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($conv['comment']); ?></td>
                    <td><?php echo $conv['action_taken']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>