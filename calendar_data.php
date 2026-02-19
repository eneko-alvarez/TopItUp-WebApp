<?php
ob_start();
session_start();
require_once 'config.php';
date_default_timezone_set('Europe/Madrid');
require 'check_session.php';

header('Content-Type: application/json');

$user_id = $_SESSION['userid'];
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

// Clamp values
if ($year  < 2000 || $year  > 2100) $year  = (int)date('Y');
if ($month < 1    || $month > 12)   $month = (int)date('m');

// 2. Get all logs for this month, grouped by date + counter_id
$stmtLogs = $pdo->prepare("
    SELECT cl.date, cl.counter_id
    FROM counter_logs cl
    JOIN counters c ON cl.counter_id = c.id
    WHERE c.user_id = ?
      AND YEAR(cl.date)  = ?
      AND MONTH(cl.date) = ?
    GROUP BY cl.date, cl.counter_id
    ORDER BY cl.date ASC
");
$stmtLogs->execute([$user_id, $year, $month]);
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Build dates map: { "YYYY-MM-DD": [counterId, counterId, ...] }
$dates = [];
foreach ($logs as $log) {
    $d = $log['date'];
    $cid = (int)$log['counter_id'];
    if (!isset($dates[$d])) {
        $dates[$d] = [];
    }
    if (!in_array($cid, $dates[$d])) {
        $dates[$d][] = $cid;
    }
}

echo json_encode([
    'year'     => $year,
    'month'    => $month,
    'dates'    => $dates,
]);
