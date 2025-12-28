<?php
session_start();
require_once 'config.php';

if (isset($_COOKIE["rememberuser"])) {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE token = ?");
    $stmt->execute([$_COOKIE["rememberuser"]]);
    // Eliminar cookie con los mismos parámetros que al crearla
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    setcookie("rememberuser", "", [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => $isHttps ? 'None' : 'Lax'
    ]);
}

session_destroy();
header("Location: index.php");
exit;
?>
