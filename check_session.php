<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION["userid"])) {
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
            $_SESSION["userid"] = $session["userid"];
            $_SESSION["username"] = $session["username"];
        } else {
            setcookie("rememberuser", "", time() - 3600, "/");
            header("Location: index.php");
            exit;
        }
    } else {
        header("Location: index.php");
        exit;
    }
}
?>
