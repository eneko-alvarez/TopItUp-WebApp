<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';
require_once 'check_session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_id'])) {
    $log_id = (int)$_POST['log_id'];
    $user_id = $_SESSION['userid'];
    $return_url = $_POST['return_url'] ?? 'dashboard.php?page=dashboard';
    
    try {
        $pdo->beginTransaction();
        
        // Verify ownership and get counter_id
        $stmt = $pdo->prepare("
            SELECT cl.counter_id 
            FROM counter_logs cl
            JOIN counters c ON cl.counter_id = c.id
            WHERE cl.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$log_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Delete the log
            $stmt = $pdo->prepare("DELETE FROM counter_logs WHERE id = ?");
            $stmt->execute([$log_id]);
            
            // Decrement the counter
            $stmt = $pdo->prepare("UPDATE counters SET count = count - 1 WHERE id = ? AND count > 0");
            $stmt->execute([$result['counter_id']]);
            
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
    }
    
    header('Location: ' . $return_url);
    exit;
}

// If accessed without POST, redirect to dashboard
header('Location: dashboard.php?page=dashboard');
exit;
?>
