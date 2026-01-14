<?php
// Verificar primero si ya hay sesión activa de PHP
if (isset($_SESSION['userid'])) {
    // Usuario ya autenticado en esta petición
    return;
}

// Si no hay sesión, verificar cookie de remember
if (!isset($_COOKIE['rememberuser'])) {
    header("Location: index.php");
    exit();
}

$token = $_COOKIE['rememberuser'];
$stmt = $pdo->prepare("SELECT us.*, u.username FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.token = ? AND us.expires_at > NOW()");
$stmt->execute([$token]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    // Token inválido, eliminar cookie
    setcookie("rememberuser", "", [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    header("Location: index.php");
    exit();
}

// Restaurar sesión desde la cookie válida
$_SESSION['userid'] = $result['user_id'];
$_SESSION['username'] = $result['username'];
?>