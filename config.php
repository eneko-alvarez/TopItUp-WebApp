<?php
// Development Mode (set to false in production) ?standalone=true
define('DEVELOPMENT_MODE', true); // Change to false when deploying to production

// Database configuration

$host = 'sql100.infinityfree.com';
$dbname = 'if0_40697566_topituip';
$username = 'if0_40697566';
$password = 'gcoPJgXrZ0cX';

/*

//esta es la dummy
$host = 'bu1h1d02lfvrdpfmi5rw-mysql.services.clever-cloud.com';
$dbname = 'bu1h1d02lfvrdpfmi5rw';
$username = 'u2r1z0uev8a5vzpn';
$password = 'RNzVZHNRQSGBTXlYedOa';
*/


// Brevo API configuration
$brevoApiKey = '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';

// Push notifications (VAPID) keys - generate your own with web-push library or tool
// You can leave the private key unused during frontend tests, but it will be
// required by server logic when sending notifications.
define('VAPID_PUBLIC_KEY', 'YOUR_VAPID_PUBLIC_KEY');
define('VAPID_PRIVATE_KEY', 'YOUR_VAPID_PRIVATE_KEY');

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
