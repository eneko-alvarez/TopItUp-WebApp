<?php

function getUserGroups($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM counter_groups 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGroupCounters($pdo, $group_id) {
    $stmt = $pdo->prepare("
        SELECT c.*, gc.id AS group_counter_id 
        FROM counters c 
        JOIN group_counters gc ON c.id = gc.counter_id 
        WHERE gc.group_id = ? 
        ORDER BY c.name ASC
    ");
    $stmt->execute([$group_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGroupTotal($pdo, $group_id) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(c.count), 0) AS total 
        FROM counters c 
        JOIN group_counters gc ON c.id = gc.counter_id 
        WHERE gc.group_id = ?
    ");
    $stmt->execute([$group_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['total'];
}

function getUserCounters($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM counters 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUnassignedCounters($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT c.* 
        FROM counters c 
        WHERE c.user_id = ? 
        AND c.id NOT IN (SELECT counter_id FROM group_counters) 
        ORDER BY c.name ASC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCounterLastLog($pdo, $counter_id) {
    $stmt = $pdo->prepare("
        SELECT date, hour 
        FROM counter_logs 
        WHERE counter_id = ? 
        ORDER BY date DESC, hour DESC 
        LIMIT 1
    ");
    $stmt->execute([$counter_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
