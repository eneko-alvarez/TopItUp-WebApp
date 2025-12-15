<?php
// Helper functions for leaderboard management

function generateInviteCode($pdo) {
    // Generate unique 8-character invite code
    do {
        $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        $stmt = $pdo->prepare("SELECT id FROM leaderboards WHERE invite_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    
    return $code;
}

function getUserLeaderboards($pdo, $user_id) {
    // Get all leaderboards where user is creator OR member
    try {
        // Simplified query without JOINs that might timeout
        $stmt = $pdo->prepare("
            SELECT DISTINCT l.id, l.name, l.invite_code, l.creator_id, l.created_at,
                   lm.counter_id, 
                   lm.group_id,
                   (l.creator_id = ?) as is_creator
            FROM leaderboards l
            LEFT JOIN leaderboard_members lm ON l.id = lm.leaderboard_id AND lm.user_id = ?
            WHERE l.creator_id = ? OR lm.user_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
        $leaderboards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get counter/group names separately to avoid JOIN complexity
        foreach ($leaderboards as &$lb) {
            $lb['counter_name'] = null;
            $lb['group_name'] = null;
            
            if ($lb['counter_id']) {
                $stmt = $pdo->prepare("SELECT name FROM counters WHERE id = ?");
                $stmt->execute([$lb['counter_id']]);
                $lb['counter_name'] = $stmt->fetchColumn();
            }
            
            if ($lb['group_id']) {
                $stmt = $pdo->prepare("SELECT name FROM counter_groups WHERE id = ?");
                $stmt->execute([$lb['group_id']]);
                $lb['group_name'] = $stmt->fetchColumn();
            }
        }
        
        return $leaderboards;
    } catch (PDOException $e) {
        error_log("getUserLeaderboards error: " . $e->getMessage());
        return [];
    }
}

function getLeaderboardRankings($pdo, $leaderboard_id) {
    // Get leaderboard time span
    try {
        $stmt = $pdo->prepare("SELECT start_date, end_date FROM leaderboards WHERE id = ?");
        $stmt->execute([$leaderboard_id]);
        $timespan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $startDate = ($timespan && !empty($timespan['start_date'])) ? $timespan['start_date'] : date('Y') . '-01-01';
        $endDate = ($timespan && !empty($timespan['end_date'])) ? $timespan['end_date'] : date('Y') . '-12-31';
    } catch (PDOException $e) {
        // If columns don't exist, use current year as default
        $startDate = date('Y') . '-01-01';
        $endDate = date('Y') . '-12-31';
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            COALESCE(
                CASE 
                    WHEN lm.counter_id IS NOT NULL THEN (
                        SELECT COUNT(*)
                        FROM counter_logs cl
                        WHERE cl.counter_id = lm.counter_id
                        AND cl.date BETWEEN ? AND ?
                    )
                    WHEN lm.group_id IS NOT NULL THEN (
                        SELECT COUNT(*)
                        FROM counter_logs cl
                        JOIN group_counters gc ON cl.counter_id = gc.counter_id
                        WHERE gc.group_id = lm.group_id
                        AND cl.date BETWEEN ? AND ?
                    )
                END,
            0) as total_count
        FROM leaderboard_members lm
        JOIN users u ON lm.user_id = u.id
        WHERE lm.leaderboard_id = ?
        ORDER BY total_count DESC, u.username ASC
    ");
    
    $stmt->execute([$startDate, $endDate, $startDate, $endDate, $leaderboard_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLeaderboardByInviteCode($pdo, $invite_code) {
    $stmt = $pdo->prepare("SELECT * FROM leaderboards WHERE invite_code = ?");
    $stmt->execute([$invite_code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isUserInLeaderboard($pdo, $leaderboard_id, $user_id) {
    // Check if user is creator OR member
    $stmt = $pdo->prepare("
        SELECT l.id 
        FROM leaderboards l
        LEFT JOIN leaderboard_members lm ON l.id = lm.leaderboard_id AND lm.user_id = ?
        WHERE l.id = ? AND (l.creator_id = ? OR lm.user_id = ?)
    ");
    $stmt->execute([$user_id, $leaderboard_id, $user_id, $user_id]);
    return $stmt->fetch() !== false;
}
?>
