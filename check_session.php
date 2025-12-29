<?php
// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Always validate the cookie/token instead of relying on $_SESSION
// This prevents issues with PHP's session.gc_maxlifetime
if (isset($_COOKIE["rememberuser"])) {
    $token = $_COOKIE["rememberuser"];
    $stmt = $pdo->prepare("
        SELECT user_sessions.*, users.id as userid, users.username 
        FROM user_sessions 
        JOIN users ON user_sessions.user_id = users.id 
        WHERE token = ? AND expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        // Set session variables for this request
        $_SESSION["userid"] = $session["userid"];
        $_SESSION["username"] = $session["username"];
        
        // Renovar token si está próximo a expirar (menos de 30 días restantes)
        $expiresAt = strtotime($session['expires_at']);
        $thirtyDaysFromNow = time() + (30 * 24 * 60 * 60);
        
        if ($expiresAt < $thirtyDaysFromNow) {
            $newExpiry = date('Y-m-d H:i:s', strtotime('+1 year'));
            $renewStmt = $pdo->prepare("UPDATE user_sessions SET expires_at = ? WHERE token = ?");
            $renewStmt->execute([$newExpiry, $token]);
        }
    } else {
        // Invalid or expired token - clear cookie and redirect
        setcookie("rememberuser", "", time() - 3600, "/", "", true, true);
        header("Location: index.php");
        exit;
    }
} else {
    // No cookie found - redirect to login
    header("Location: index.php");
    exit;
}
?>
