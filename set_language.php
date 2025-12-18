<?php
/**
 * AJAX Endpoint for Manual Language Switching
 * Accepts POST request with 'lang' parameter
 */

session_start();
require_once 'includes/lang.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$lang = $_POST['lang'] ?? '';

if (!in_array($lang, SUPPORTED_LANGUAGES)) {
    echo json_encode(['success' => false, 'error' => 'Invalid language']);
    exit;
}

setLang($lang);

echo json_encode([
    'success' => true,
    'lang' => $lang,
    'message' => 'Language updated successfully'
]);
