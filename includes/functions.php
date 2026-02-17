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

function getGroupCounters($pdo, $group_id, $filter_year = null) {
    if ($filter_year === null) {
        $filter_year = getUserFilterYear();
    }
    
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.color, c.user_id, c.is_public, c.created_at, c.type,
               gc.id AS group_counter_id,
               (SELECT COALESCE(SUM(number), 0) FROM counter_logs WHERE counter_id = c.id AND YEAR(date) = ?) as count
        FROM counters c 
        JOIN group_counters gc ON c.id = gc.counter_id 
        WHERE gc.group_id = ? 
        ORDER BY c.name ASC
    ");
    $stmt->execute([$filter_year, $group_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGroupTotal($pdo, $group_id, $filter_year = null) {
    if ($filter_year === null) {
        $filter_year = getUserFilterYear();
    }
    
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(cl.number), 0) AS total 
        FROM counter_logs cl
        JOIN group_counters gc ON cl.counter_id = gc.counter_id 
        WHERE gc.group_id = ? AND YEAR(cl.date) = ?
    ");
    $stmt->execute([$group_id, $filter_year]);
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

function getUnassignedCounters($pdo, $user_id, $filter_year = null) {
    if ($filter_year === null) {
        $filter_year = getUserFilterYear();
    }
    
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.color, c.user_id, c.is_public, c.created_at, c.type,
               (SELECT COALESCE(SUM(number), 0) FROM counter_logs WHERE counter_id = c.id AND YEAR(date) = ?) as count
        FROM counters c 
        WHERE c.user_id = ? 
        AND c.id NOT IN (SELECT counter_id FROM group_counters) 
        ORDER BY c.name ASC
    ");
    $stmt->execute([$filter_year, $user_id]);
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

// Year filtering helpers
function getUserFilterYear() {
    // Check cookie first
    if (isset($_COOKIE['filter_year'])) {
        return (int)$_COOKIE['filter_year'];
    }
    // Default to current year
    return (int)date('Y');
}

function getAvailableYears($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT YEAR(cl.date) as year
        FROM counter_logs cl
        JOIN counters c ON cl.counter_id = c.id
        WHERE c.user_id = ?
        ORDER BY year DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

?>
