<?php
// Development Mode (set to false in production)
define('DEVELOPMENT_MODE', true); // Change to false when deploying to production

// Database configuration
$host = 'sql100.infinityfree.com';
$dbname = 'if0_40697566_topituip';
$username = 'if0_40697566';
$password = 'gcoPJgXrZ0cX';

/*
$host = 'bu1h1d02lfvrdpfmi5rw-mysql.services.clever-cloud.com';
$dbname = 'bu1h1d02lfvrdpfmi5rw';
$username = 'u2r1z0uev8a5vzpn';
$password = 'RNzVZHNRQSGBTXlYedOa';
*/


// Brevo API configuration
$brevoApiKey = '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';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Load language system
require_once __DIR__ . '/includes/lang.php';
?>
