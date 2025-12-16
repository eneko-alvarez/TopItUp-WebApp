<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_SESSION['userid'])) {
    try {
        $user_id = (int)$_SESSION['userid'];
        
        // Iniciar transacción para asegurar atomicidad
        $pdo->beginTransaction();
        
        // Marcar tour como completado
        $stmt = $pdo->prepare("UPDATE users SET first_login = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Borrar todos los grupos del usuario (CASCADE borrará group_counters automáticamente)
        $stmt = $pdo->prepare("DELETE FROM counter_groups WHERE user_id = ?");
        $groups_deleted = $stmt->execute([$user_id]);
        $groups_count = $stmt->rowCount();
        
        // Borrar todos los contadores del usuario (CASCADE borrará logs automáticamente)
        $stmt = $pdo->prepare("DELETE FROM counters WHERE user_id = ?");
        $counters_deleted = $stmt->execute([$user_id]);
        $counters_count = $stmt->rowCount();
        
        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tour completado y datos de demostración eliminados',
            'user_id' => $user_id,
            'groups_deleted' => $groups_count,
            'counters_deleted' => $counters_count
        ]);
        
    } catch (PDOException $e) {
        // Revertir cambios si algo falla
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'No active session'
    ]);
}
?>
