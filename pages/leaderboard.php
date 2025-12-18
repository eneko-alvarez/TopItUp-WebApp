<?php
require_once 'includes/functions.php';
require_once 'includes/leaderboard_functions.php';

// Get user's leaderboards
$userLeaderboards = getUserLeaderboards($pdo, $user_id);
?>

<div class="leaderboard-page">
    <h2><i class="fas fa-trophy"></i> <?= t('leaderboard.my_leaderboards') ?></h2>

    <!-- User's Leaderboards -->
    <?php if (empty($userLeaderboards)): ?>
        <div class="empty-state">
            <i class="fas fa-trophy" style="font-size: 48px; color: #ccc;"></i>
            <p><?= t('leaderboard.empty') ?></p>
        </div>
    <?php else: ?>
        <div class="leaderboards-grid">
            <?php foreach ($userLeaderboards as $leaderboard): ?>
                <div class="leaderboard-card">
                    <div class="leaderboard-header">
                        <h4><?= htmlspecialchars($leaderboard['name']) ?></h4>
                        <?php if ($leaderboard['is_creator']): ?>
                            <span class="badge-creator"><i class="fas fa-crown"></i> Creator</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="leaderboard-stats">
                        <div class="stat">
                            <span class="stat-label"><?= t('leaderboard.view.settings') ?>:</span>
                            <span class="stat-value">
                                <?php if ($leaderboard['counter_name']): ?>
                                    <i class="fas fa-bullseye"></i> <?= htmlspecialchars($leaderboard['counter_name']) ?>
                                <?php elseif ($leaderboard['group_name']): ?>
                                    <i class="fas fa-layer-group"></i> <?= htmlspecialchars($leaderboard['group_name']) ?>
                                <?php else: ?>
                                    <i class="fas fa-question"></i> <?= t('common.error') ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <a href="?page=leaderboard_view&id=<?= $leaderboard['id'] ?>" class="btn-view">
                        <i class="fas fa-chart-line"></i> <?= t('leaderboard.view.rankings') ?>
                    </a>
                    
                    <a href="?page=leaderboard_settings&id=<?= $leaderboard['id'] ?>" class="btn-settings" style="display: block; width: 100%; text-align: center; padding: 10px; background: #2a2a2a; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 10px; border: 1px solid #404040;">
                        <i class="fas fa-cog"></i> <?= t('leaderboard.view.settings') ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

     <!-- Create New Leaderboard -->
    <div class="leaderboard-create-section">
        <h3><?= t('leaderboard.create.title') ?></h3>
        <p style="margin: 10px 0; color: #a0a0a0;"><?= t('leaderboard.empty') ?></p>
        <a href="create_leaderboard.php" class="btn-primary" style="display: inline-block; padding: 12px 24px; text-decoration: none;">
            <i class="fas fa-plus"></i> <?= t('leaderboard.create.submit') ?>
        </a>
    </div>

    <!-- Join Leaderboard Section -->
    <div class="leaderboard-join-section">
        <h3><?= t('leaderboard.join.title') ?></h3>
        <p><?= t('leaderboard.join.code') ?>?</p>
        <form action="join_leaderboard.php" method="GET" class="inline-form">
            <input type="text" name="code" placeholder="<?= t('leaderboard.join.code_placeholder') ?>" required maxlength="20" style="flex: 1; text-transform: uppercase;">
            <button type="submit" class="btn-primary"><i class="fas fa-sign-in-alt"></i> <?= t('leaderboard.join.submit') ?></button>
        </form>
    </div>
    
</div>

<script>
function copyInviteCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('Invite code copied: ' + code);
    }).catch(err => {
        prompt('Copy this code:', code);
    });
}
</script>

<style>
.leaderboard-page {
    padding: 20px;
}

.leaderboard-create-section,
.leaderboard-join-section {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    border: 1px solid #2a2a2a;
}

.leaderboard-create-section h3,
.leaderboard-join-section h3 {
    margin-top: 0;
    color: #ffffff;
}

.leaderboard-create-section p,
.leaderboard-join-section p {
    color: #a0a0a0;
}

.inline-form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.inline-form input[type="text"] {
    flex: 1;
    min-width: 150px;
    padding: 12px 15px;
    border: 2px solid #2a2a2a;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
    background: #0a0a0a;
    color: #ffffff;
    transition: border-color 0.3s ease;
}

.inline-form input[type="text"]:focus {
    border-color: #667eea;
    outline: none;
    background: #1a1a1a;
}

.inline-form .btn-primary {
    flex-shrink: 0;
    white-space: nowrap;
    padding: 12px 20px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.leaderboards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.leaderboard-card {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #2a2a2a;
}

.leaderboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.leaderboard-header h4 {
    margin: 0;
    color: #ffffff;
}

.badge-creator {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.invite-section {
    background: #262626;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid #2a2a2a;
}

.invite-section label {
    display: block;
    font-size: 12px;
    color: #a0a0a0;
    margin-bottom: 5px;
}

.invite-code-box {
    display: flex;
    gap: 8px;
    align-items: center;
}

.invite-code-box code {
    flex: 1;
    background: #0a0a0a;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    letter-spacing: 2px;
    border: 2px dashed #667eea;
    color: #ffffff;
}

.btn-copy {
    padding: 8px 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.btn-copy:hover {
    background: #5568d3;
}

.leaderboard-stats {
    margin: 15px 0;
}

.stat {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.stat-label {
    color: #a0a0a0;
    font-size: 14px;
}

.stat-value {
    font-weight: 600;
    color: #667eea;
}

.btn-view {
    display: block;
    width: 100%;
    text-align: center;
    padding: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 15px;
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
</style>
