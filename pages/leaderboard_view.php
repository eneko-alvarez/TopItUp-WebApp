<?php
require_once 'includes/functions.php';
require_once 'includes/leaderboard_functions.php';

$leaderboard_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify user is member of this leaderboard
if (!isUserInLeaderboard($pdo, $leaderboard_id, $user_id)) {
    header('Location: ?page=leaderboard');
    exit;
}

// Get leaderboard info
$stmt = $pdo->prepare("SELECT * FROM leaderboards WHERE id = ?");
$stmt->execute([$leaderboard_id]);
$leaderboard = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leaderboard) {
    header('Location: ?page=leaderboard');
    exit;
}

// Get rankings
$rankings = getLeaderboardRankings($pdo, $leaderboard_id);
?>

<div class="leaderboard-view-page">
    <div class="leaderboard-view-header">
        <h2><i class="fas fa-trophy"></i> <?= htmlspecialchars($leaderboard['name']) ?></h2>
    </div>
    
    <?php if (empty($rankings)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash" style="font-size: 48px; color: #ccc;"></i>
            <p>No members yet. Share the invite code to get people to join!</p>
        </div>
    <?php else: ?>
        <div class="rankings-table">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Get current username
                    $stmt_username = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $stmt_username->execute([$user_id]);
                    $current_username = $stmt_username->fetchColumn();
                    
                    foreach ($rankings as $index => $ranking): 
                        $is_current_user = ($ranking['username'] === $current_username);
                    ?>
                        <tr <?= $is_current_user ? 'class="current-user-row"' : '' ?>>
                            <td class="rank-cell">
                                <?php if ($index === 0): ?>
                                    <span class="medal gold"><i class="fas fa-trophy"></i></span>
                                <?php elseif ($index === 1): ?>
                                    <span class="medal silver"><i class="fas fa-medal"></i></span>
                                <?php elseif ($index === 2): ?>
                                    <span class="medal bronze"><i class="fas fa-medal"></i></span>
                                <?php else: ?>
                                    #<?= $index + 1 ?>
                                <?php endif; ?>
                            </td>
                            <td class="<?= $is_current_user ? 'current-user' : '' ?>"><?= htmlspecialchars($ranking['username']) ?></td>
                            <td class="count-cell"><?= number_format($ranking['total_count'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.leaderboard-view-page {
    padding: 20px;
}

.leaderboard-view-header {
    margin-bottom: 30px;
}

.leaderboard-view-header h2 {
    margin: 0;
    color: #ffffff;
}

.rankings-table {
    background: #1a1a1a;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #2a2a2a;
}

.rankings-table table {
    width: 100%;
    border-collapse: collapse;
}

.rankings-table th {
    background: #262626;
    color: #ffffff;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #667eea;
}

.rankings-table td {
    padding: 15px;
    border-bottom: 1px solid #2a2a2a;
    color: #ffffff;
}

.rankings-table tr:last-child td {
    border-bottom: none;
}

.rankings-table tr:hover {
    background: #262626;
}

.rank-cell {
    font-weight: bold;
    color: #667eea;
    text-align: center;
    width: 80px;
}

.count-cell {
    font-weight: bold;
    color: #28a745;
    text-align: right;
}

.medal {
    font-size: 20px;
}

.medal.gold { color: #FFD700; }
.medal.silver { color: #C0C0C0; }
.medal.bronze { color: #CD7F32; }

.current-user {
    color: #667eea;
    font-weight: bold;
}

.current-user-row {
    background: rgba(102, 126, 234, 0.2) !important;
}

.current-user-row:hover {
    background: rgba(102, 126, 234, 0.3) !important;
}
</style>
