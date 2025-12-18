<?php
require_once 'includes/functions.php';
require_once 'includes/leaderboard_functions.php';

$leaderboard_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify user is member or creator of this leaderboard
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

// Check if current user is creator
$isCreator = ($leaderboard['creator_id'] == $user_id);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'leave_leaderboard') {
            try {
                error_log("=== LEAVE LEADERBOARD START ===");
                error_log("User ID: " . $user_id);
                error_log("Leaderboard ID: " . $leaderboard_id);
                error_log("Is Creator: " . ($isCreator ? 'YES' : 'NO'));
                
                // Check if user is the creator
                if ($isCreator) {
                    // Creator leaving = delete entire leaderboard
                    // CASCADE will automatically remove all members from leaderboard_members
                    error_log("Executing DELETE leaderboard for creator");
                    $stmt = $pdo->prepare("DELETE FROM leaderboards WHERE id = ? AND creator_id = ?");
                    $stmt->execute([$leaderboard_id, $user_id]);
                    $rowCount = $stmt->rowCount();
                    error_log("Rows deleted from leaderboards: " . $rowCount);
                } else {
                    // Regular member leaving = remove their membership only
                    error_log("Executing DELETE membership for member");
                    $stmt = $pdo->prepare("DELETE FROM leaderboard_members WHERE leaderboard_id = ? AND user_id = ?");
                    $stmt->execute([$leaderboard_id, $user_id]);
                    $rowCount = $stmt->rowCount();
                    error_log("Rows deleted from leaderboard_members: " . $rowCount);
                }
                
                error_log("=== LEAVE LEADERBOARD SUCCESS - REDIRECTING ===");
                header('Location: ?page=leaderboard');
                exit;
            } catch (PDOException $e) {
                error_log("=== LEAVE LEADERBOARD ERROR ===");
                error_log("Error: " . $e->getMessage());
                error_log("SQL State: " . $e->getCode());
                // Redirect anyway to avoid infinite loading
                header('Location: ?page=leaderboard');
                exit;
            }
        } elseif ($_POST['action'] === 'change_source') {
            $source_type = $_POST['source_type'] ?? '';
            $source_id = (int)($_POST['source_id'] ?? 0);
            
            $counter_id = $source_type === 'counter' ? $source_id : null;
            $group_id = $source_type === 'group' ? $source_id : null;
            
            $stmt = $pdo->prepare("UPDATE leaderboard_members SET counter_id = ?, group_id = ? WHERE leaderboard_id = ? AND user_id = ?");
            $stmt->execute([$counter_id, $group_id, $leaderboard_id, $user_id]);
            
            header('Location: ?page=leaderboard_settings&id=' . $leaderboard_id);
            exit;
        } elseif ($_POST['action'] === 'update_timespan') {
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            
            if (!empty($start_date) && !empty($end_date)) {
                $stmt = $pdo->prepare("UPDATE leaderboards SET start_date = ?, end_date = ? WHERE id = ? AND creator_id = ?");
                $stmt->execute([$start_date, $end_date, $leaderboard_id, $user_id]);
            }
            
            header('Location: ?page=leaderboard_settings&id=' . $leaderboard_id);
            exit;
        }
    }
}

// Get members
$stmt = $pdo->prepare("
    SELECT lm.*, u.username, c.name as counter_name, cg.name as group_name
    FROM leaderboard_members lm
    JOIN users u ON lm.user_id = u.id
    LEFT JOIN counters c ON lm.counter_id = c.id
    LEFT JOIN counter_groups cg ON lm.group_id = cg.id
    WHERE lm.leaderboard_id = ?
    ORDER BY u.username ASC
");
$stmt->execute([$leaderboard_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current user's membership
$stmt = $pdo->prepare("SELECT * FROM leaderboard_members WHERE leaderboard_id = ? AND user_id = ?");
$stmt->execute([$leaderboard_id, $user_id]);
$myMembership = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's counters and groups
$userCounters = getUserCounters($pdo, $user_id);
$userGroups = getUserGroups($pdo, $user_id);
?>

<div class="leaderboard-settings-page">
    <div class="settings-header">
        <h2><i class="fas fa-cog"></i> <?= htmlspecialchars($leaderboard['name']) ?> - <?= t('leaderboard.view.settings') ?></h2>
    </div>
    
    
    <!-- Invite Code Section (Creator only) -->
    <?php if ($isCreator): ?>
    <div class="settings-section">
        <h3><?= t('leaderboard.settings.invite_code') ?></h3>
        <div class="invite-code-display">
            <code><?= htmlspecialchars($leaderboard['invite_code']) ?></code>
            <button onclick="copyInviteCode('<?= htmlspecialchars($leaderboard['invite_code']) ?>')" class="btn-copy">
                <i class="fas fa-copy"></i> <?= t('common.copy') ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Members List -->
    <div class="settings-section">
        <h3><?= t('leaderboard.settings.members') ?> (<?= count($members) ?>)</h3>
        <?php if (empty($members)): ?>
            <p style="color: #999;"><?= t('leaderboard.settings.no_members') ?></p>
        <?php else: ?>
            <div class="members-list">
                <?php foreach ($members as $member): ?>
                    <div class="member-item">
                        <div class="member-info">
                            <strong><?= htmlspecialchars($member['username']) ?></strong>
                            <?php if ($member['user_id'] == $leaderboard['creator_id']): ?>
                                <span class="badge-creator"><i class="fas fa-crown"></i> Creator</span>
                            <?php endif; ?>
                            <br>
                            <small style="color: #666;">
                                <?= t('leaderboard.view.settings') ?>: 
                                <?php if ($member['counter_name']): ?>
                                    <i class="fas fa-bullseye"></i> <?= htmlspecialchars($member['counter_name']) ?>
                                <?php elseif ($member['group_name']): ?>
                                    <i class="fas fa-layer-group"></i> <?= htmlspecialchars($member['group_name']) ?>
                                <?php else: ?>
                                    <i class="fas fa-question"></i> <?= t('common.error') ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Time Span Section (Creator only) -->
    <?php if ($isCreator): ?>
    <div class="settings-section">
        <h3><?= t('leaderboard.settings.timespan.title') ?></h3>
        <p style="color: #666; margin-bottom: 15px;"><?= t('leaderboard.settings.timespan.description') ?></p>
        <form method="POST" class="timespan-form">
            <input type="hidden" name="action" value="update_timespan">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label><?= t('leaderboard.settings.timespan.start') ?>:</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($leaderboard['start_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label><?= t('leaderboard.settings.timespan.end') ?>:</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($leaderboard['end_date']) ?>" required>
                </div>
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-calendar"></i> <?= t('leaderboard.settings.timespan.submit') ?>
            </button>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Change Source Section -->
    <?php if ($myMembership): ?>
        <div class="settings-section">
            <h3><?= t('leaderboard.settings.change_tracking') ?></h3>
            <form method="POST" class="change-source-form" onsubmit="return prepareSourceSubmit()">
                <input type="hidden" name="action" value="change_source">
                <input type="hidden" name="source_id" id="finalSourceId" value="">
                
                <div class="form-group">
                    <label><?= t('common.track') ?>:</label>
                    <select name="source_type" id="sourceType" required onchange="updateSourceOptions()">
                        <option value="">-- <?= t('common.select') ?> --</option>
                        <option value="counter" <?= $myMembership['counter_id'] ? 'selected' : '' ?>><?= t('leaderboard.settings.single_counter') ?></option>
                        <option value="group" <?= $myMembership['group_id'] ? 'selected' : '' ?>><?= t('leaderboard.settings.counter_group') ?></option>
                    </select>
                </div>
                
                <div class="form-group" id="counterSelect" style="display: <?= $myMembership['counter_id'] ? 'block' : 'none' ?>;">
                    <label><?= t('leaderboard.settings.select_counter') ?>:</label>
                    <select id="counterOptions">
                        <?php foreach ($userCounters as $counter): ?>
                            <option value="<?= $counter['id'] ?>" <?= $counter['id'] == $myMembership['counter_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($counter['name']) ?> (<?= $counter['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="groupSelect" style="display: <?= $myMembership['group_id'] ? 'block' : 'none' ?>;">
                    <label><?= t('leaderboard.settings.select_group') ?>:</label>
                    <select id="groupOptions">
                        <?php foreach ($userGroups as $group): ?>
                            <option value="<?= $group['id'] ?>" <?= $group['id'] == $myMembership['group_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> <?= t('common.save') ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- Leave Leaderboard -->
    <div class="settings-section danger-zone">
        <h3><?= t('leaderboard.settings.danger_zone') ?></h3>
        <p style="color: #666; margin-bottom: 15px;">
            <?= t('leaderboard.settings.leave_message') ?>
        </p>
        <form method="POST" onsubmit="return confirm('Are you sure you want to leave this leaderboard?');">
            <input type="hidden" name="action" value="leave_leaderboard">
            <button type="submit" class="btn-danger">
                <i class="fas fa-sign-out-alt"></i> <?= t('leaderboard.settings.leave') ?>
            </button>
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

function prepareSourceSubmit() {
    const sourceType = document.getElementById('sourceType').value;
    const finalSourceId = document.getElementById('finalSourceId');
    
    console.log('prepareSourceSubmit - sourceType:', sourceType);
    
    if (sourceType === 'counter') {
        const counterValue = document.getElementById('counterOptions').value;
        console.log('Setting counter value:', counterValue);
        finalSourceId.value = counterValue;
    } else if (sourceType === 'group') {
        const groupValue = document.getElementById('groupOptions').value;
        console.log('Setting group value:', groupValue);
        finalSourceId.value = groupValue;
    } else {
        alert('Please select a type');
        return false;
    }
    
    console.log('Final source_id:', finalSourceId.value);
    return true;
}

function updateSourceOptions() {
    const sourceType = document.getElementById('sourceType').value;
    const counterSelect = document.getElementById('counterSelect');
    const groupSelect = document.getElementById('groupSelect');
    const counterOptions = document.getElementById('counterOptions');
    const groupOptions = document.getElementById('groupOptions');
    
    if (sourceType === 'counter') {
        counterSelect.style.display = 'block';
        groupSelect.style.display = 'none';
        counterOptions.required = true;
        groupOptions.required = false;
    } else if (sourceType === 'group') {
        counterSelect.style.display = 'none';
        groupSelect.style.display = 'block';
        counterOptions.required = false;
        groupOptions.required = true;
    } else {
        counterSelect.style.display = 'none';
        groupSelect.style.display = 'none';
        counterOptions.required = false;
        groupOptions.required = false;
    }
}
</script>

<style>
.leaderboard-settings-page {
    padding: 20px;
}

.settings-header {
    margin-bottom: 30px;
}

.settings-header h2 {
    margin: 0;
    color: #ffffff;
}

.settings-section {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #2a2a2a;
}

.settings-section h3 {
    margin-top: 0;
    color: #ffffff;
}

.settings-section p {
    color: #a0a0a0;
}

.invite-code-display {
    display: flex;
    gap: 10px;
    align-items: center;
}

.invite-code-display code {
    flex: 1;
    background: #0a0a0a;
    padding: 12px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: bold;
    letter-spacing: 2px;
    border: 2px dashed #667eea;
    color: #ffffff;
}

.btn-copy {
    padding: 12px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.btn-copy:hover {
    background: #5568d3;
}

.members-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.member-item {
    padding: 15px;
    background: #262626;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #2a2a2a;
}

.member-item strong {
    color: #ffffff;
}

.member-item small {
    color: #a0a0a0;
}

.member-info {
    flex: 1;
}

.change-source-form .form-group {
    margin-bottom: 15px;
}

.change-source-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #ffffff;
}

.change-source-form select,
.timespan-form input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 2px solid #2a2a2a;
    border-radius: 8px;
    font-size: 14px;
    background: #0a0a0a;
    color: #ffffff;
    transition: border-color 0.3s ease;
}

.change-source-form select:focus,
.timespan-form input[type="date"]:focus {
    border-color: #667eea;
    outline: none;
    background: #1a1a1a;
}

.timespan-form label {
    color: #ffffff;
    font-weight: 600;
    display: block;
    margin-bottom: 5px;
}

.danger-zone {
    border: 2px solid #dc3545;
    background: rgba(220, 53, 69, 0.05) !important;
}

.danger-zone h3 {
    color: #dc3545;
}

.btn-danger {
    background: #dc3545;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.btn-danger:hover {
    background: #c82333;
}
</style>
