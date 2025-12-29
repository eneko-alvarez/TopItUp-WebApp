<?php
session_start();
require_once 'config.php';

if (isset($_COOKIE["rememberuser"])) {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE token = ?");
    $stmt->execute([$_COOKIE["rememberuser"]]);
    // Eliminar cookie con sintaxis tradicional
    setcookie("rememberuser", "", time() - 3600, "/", "", true, true);
}

session_destroy();
header("Location: index.php");
exit;
?>
