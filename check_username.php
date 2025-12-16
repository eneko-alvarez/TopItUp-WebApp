<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        echo json_encode(['available' => false, 'message' => 'Username cannot be empty']);
        exit;
    }
    
    if (strlen($username) < 3) {
        echo json_encode(['available' => false, 'message' => 'Username must be at least 3 characters']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['available' => false, 'message' => 'Username already taken']);
        } else {
            echo json_encode(['available' => true, 'message' => 'Username available']);
        }
    } catch(PDOException $e) {
        echo json_encode(['available' => false, 'message' => 'Error checking username']);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request']);
}
