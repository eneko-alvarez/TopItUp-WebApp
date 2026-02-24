<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'check_session.php';

$user_id = $_SESSION['userid'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['group_id']) || !isset($input['is_expanded'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters.']);
    exit;
}

$group_id = (int)$input['group_id'];
$is_expanded = $input['is_expanded'] ? 1 : 0;

// Verify the group belongs to the authenticated user
$stmt = $pdo->prepare("SELECT id FROM counter_groups WHERE id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Group not found or unauthorized.']);
    exit;
}

// Update the is_public value
$updateStmt = $pdo->prepare("UPDATE counter_groups SET is_public = ? WHERE id = ?");
if ($updateStmt->execute([$is_expanded, $group_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update group.']);
}
?>
