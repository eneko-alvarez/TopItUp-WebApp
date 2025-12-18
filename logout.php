<?php
session_start();
require_once 'config.php';

if (isset($_COOKIE["rememberuser"])) {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE token = ?");
    $stmt->execute([$_COOKIE["rememberuser"]]);
    // Eliminar cookie con los mismos parámetros que al crearla
    setcookie("rememberuser", "", [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

session_destroy();
header("Location: index.php");
exit;
?>
