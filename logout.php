<?php
session_start();
require_once 'config.php';

if (isset($_COOKIE["rememberuser"])) {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE token = ?");
    $stmt->execute([$_COOKIE["rememberuser"]]);
    // TEST: Eliminar cookie sin secure flag
    setcookie("rememberuser", "", time() - 3600, "/");
}

// Delete year filter cookie on logout
if (isset($_COOKIE["filter_year"])) {
    setcookie("filter_year", "", time() - 3600, "/");
}

session_destroy();
header("Location: index.php");
exit;
?>
