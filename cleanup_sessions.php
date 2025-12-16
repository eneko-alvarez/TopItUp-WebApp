<?php
/**
 * Cleanup Expired Sessions
 * 
 * This script removes expired session tokens from the database.
 * Run this periodically via Windows Task Scheduler or manually.
 * 
 * Example Task Scheduler command:
 * php "d:\alvar\VisualStudio_Projects\TopItUp\cleanup_sessions.php"
 */

require_once 'config.php';

try {
    // Delete expired sessions
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
    $stmt->execute();
    
    $deletedCount = $stmt->rowCount();
    
    echo "Cleanup completed successfully.\n";
    echo "Expired sessions deleted: $deletedCount\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
