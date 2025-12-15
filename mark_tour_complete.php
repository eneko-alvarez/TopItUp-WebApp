<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_SESSION['userid'])) {
    try {
        $user_id = (int)$_SESSION['userid'];
        
        $stmt = $pdo->prepare("UPDATE users SET first_login = 0 WHERE id = ?");
        $result = $stmt->execute([$user_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Tour completado correctamente',
                'user_id' => $user_id,
                'rows_affected' => $stmt->rowCount()
            ]);
        } else {
            $checkStmt = $pdo->prepare("SELECT first_login FROM users WHERE id = ?");
            $checkStmt->execute([$user_id]);
            $currentValue = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => false, 
                'message' => 'No se actualizó ninguna fila',
                'user_id' => $user_id,
                'current_first_login' => $currentValue['first_login'] ?? 'no encontrado'
            ]);
        }
    } catch (PDOException $e) {
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
