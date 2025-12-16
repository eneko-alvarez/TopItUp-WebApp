<?php
// Database configuration
$host = 'bu1h1d02lfvrdpfmi5rw-mysql.services.clever-cloud.com';
$dbname = 'bu1h1d02lfvrdpfmi5rw';
$username = 'u2r1z0uev8a5vzpn';
$password = 'RNzVZHNRQSGBTXlYedOa';

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
?>
