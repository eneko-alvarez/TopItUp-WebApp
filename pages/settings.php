<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_group') {
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#667eea';
        
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO counter_groups (user_id, name, color, is_public) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user_id, $name, $color]);
        }
        header('Location: dashboard.php?page=settings');
        exit;
    }
    
    if ($action === 'delete_group') {
        $group_id = $_POST['group_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM counter_groups WHERE id = ? AND user_id = ?");
        $stmt->execute([$group_id, $user_id]);
        header('Location: dashboard.php?page=settings');
        exit;
    }
    
    if ($action === 'create_counter') {
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#667eea';
        
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO counters (user_id, name, color, is_public) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user_id, $name, $color]);
        }
        header('Location: dashboard.php?page=settings');
        exit;
    }
    
    if ($action === 'delete_counter') {
        $counter_id = $_POST['counter_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM counters WHERE id = ? AND user_id = ?");
        $stmt->execute([$counter_id, $user_id]);
        header('Location: dashboard.php?page=settings');
        exit;
    }
    

    
    if ($action === 'assign_counter') {
        $counter_id = $_POST['counter_id'] ?? 0;
        $group_id = $_POST['group_id'] ?? 0;
        
        if ($counter_id && $group_id) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO group_counters (group_id, counter_id) VALUES (?, ?)");
                $stmt->execute([$group_id, $counter_id]);
            } catch (PDOException $e) {}
        }
        header('Location: dashboard.php?page=settings');
        exit;
    }
    
    if ($action === 'unassign_counter') {
        $group_counter_id = $_POST['group_counter_id'] ?? 0;
        $stmt = $pdo->prepare("
            DELETE gc FROM group_counters gc
            JOIN counter_groups cg ON gc.group_id = cg.id
            WHERE gc.id = ? AND cg.user_id = ?
        ");
        $stmt->execute([$group_counter_id, $user_id]);
        header('Location: dashboard.php?page=settings');
        exit;
    }
}

$groups = getUserGroups($pdo, $user_id);
$unassignedCounters = getUnassignedCounters($pdo, $user_id);

$groupsWithCounters = [];
foreach ($groups as $group) {
    $counters = getGroupCounters($pdo, $group['id']);
    $groupsWithCounters[] = [
        'group' => $group,
        'counters' => $counters
    ];
}
?>

<div class="settings-page">
    <div class="settings-section">
        <h3><i class="fas fa-plus-square"></i> Create Counter</h3>
        <form method="POST" class="settings-form">
            <input type="hidden" name="action" value="create_counter">
            <div class="form-group">
                <label for="counter_name">Counter Name:</label>
                <input type="text" id="counter_name" name="name" required placeholder="e.g.: Gym Sessions">
            </div>
            <div class="form-group">
                <label for="counter_color">Color:</label>
                <input type="color" id="counter_color" name="color" value="#667eea" class="color-picker">
            </div>

            <button type="submit" class="btn-primary">Create Counter</button>
        </form>
    </div>

    <div class="settings-section">
        <h3><i class="fas fa-layer-group"></i> Create New Group</h3>
        <form method="POST" class="settings-form">
            <input type="hidden" name="action" value="create_group">
            <div class="form-group">
                <label for="group_name">Group Name:</label>
                <input type="text" id="group_name" name="name" required placeholder="e.g.: Drinks">
            </div>
            <div class="form-group">
                <label for="group_color">Color:</label>
                <input type="color" id="group_color" name="color" value="#667eea" class="color-picker">
            </div>
            <button type="submit" class="btn-primary">Create Group</button>
        </form>
    </div>
    
    <div class="settings-section">
        <h3><i class="fas fa-tasks"></i> My Groups</h3>
        <?php if (empty($groupsWithCounters)): ?>
            <p class="empty-message">You have no groups created.</p>
        <?php else: ?>
            <?php foreach ($groupsWithCounters as $data): ?>
                <div class="group-management-card">
                    <div class="group-management-header">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="group-management-name" style="color: <?= htmlspecialchars($data['group']['color']) ?>">
                                <?= htmlspecialchars($data['group']['name']) ?>
                            </span>
                        </div>
                        <div class="group-management-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_group">
                                <input type="hidden" name="group_id" value="<?= $data['group']['id'] ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Delete group? Counters will be kept.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (!empty($data['counters'])): ?>
                        <div class="group-counters-list">
                            <?php foreach ($data['counters'] as $counter): ?>
                                <div class="group-counter-item">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="color: <?= htmlspecialchars($counter['color']) ?>; font-weight: 600;">
                                            <?= htmlspecialchars($counter['name']) ?> 
                                            <span style="color: #999; font-weight: normal;">(<?= $counter['count'] ?>)</span>
                                        </span>
                                        <?php
                                        // Check if counter is in any leaderboard
                                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM leaderboard_members WHERE counter_id = ?");
                                        $stmt_check->execute([$counter['id']]);
                                        $in_leaderboard = $stmt_check->fetchColumn() > 0;
                                        ?>
                                        <span class="leaderboard-indicator <?= $in_leaderboard ? 'active' : '' ?>" title="<?= $in_leaderboard ? 'In leaderboard' : 'Not in leaderboard' ?>"></span>
                                    </div>
                                    <div style="display: flex; gap: 5px;">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="unassign_counter">
                                            <input type="hidden" name="group_counter_id" value="<?= $counter['group_counter_id'] ?>">
                                            <button type="submit" class="btn-small-danger">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-group-counters">No counters assigned</p>
                    <?php endif; ?>
                    
                    <!-- Add Counter Form for this specific group -->
                    <?php if (!empty($unassignedCounters)): ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                <input type="hidden" name="action" value="assign_counter">
                                <input type="hidden" name="group_id" value="<?= $data['group']['id'] ?>">
                                <select name="counter_id" required style="flex: 1; padding: 8px; border: 2px solid #e9ecef; border-radius: 6px; font-size: 14px;">
                                    <option value="">+ Add Counter</option>
                                    <?php foreach ($unassignedCounters as $counter): ?>
                                        <option value="<?= $counter['id'] ?>">
                                            <?= htmlspecialchars($counter['name']) ?> (<?= $counter['count'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-primary" style="padding: 8px 16px; white-space: nowrap;">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    
    <div class="settings-section">
        <h3><i class="fas fa-unlink"></i> Single Counters</h3>
        <?php if (empty($unassignedCounters)): ?>
            <p class="empty-message">All counters are assigned to groups.</p>
        <?php else: ?>
            <div class="unassigned-counters-list">
                <?php foreach ($unassignedCounters as $counter): ?>
                    <div class="unassigned-counter-item">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="color: <?= htmlspecialchars($counter['color']) ?>; font-weight: 600;">
                                <?= htmlspecialchars($counter['name']) ?> 
                                <span style="color: #999; font-weight: normal;">(<?= $counter['count'] ?>)</span>
                            </span>
                            <span class="privacy-tag <?= $counter['is_public'] ? 'public' : 'private' ?>"></span>
                        </div>
                        <div style="display: flex; gap: 5px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_counter_visibility">
                                <input type="hidden" name="counter_id" value="<?= $counter['id'] ?>">
                                <button type="submit" class="btn-small-toggle">
                                    <i class="fas fa-eye<?= $counter['is_public'] ? '-slash' : '' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_counter">
                                <input type="hidden" name="counter_id" value="<?= $counter['id'] ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Delete counter?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="settings-section">
        <div class="logout-container">
            <p>User: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
            <a href="logout.php" class="btn-danger" style="display: inline-block; text-decoration: none; margin-top: 1rem;">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </div>
    </div>
</div>
