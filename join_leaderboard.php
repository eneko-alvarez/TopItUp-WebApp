<?php
session_start();
require_once 'config.php';
require_once 'check_session.php';
require_once 'includes/functions.php';
require_once 'includes/leaderboard_functions.php';

$user_id = $_SESSION['userid'];
$error = null;
$leaderboard = null;

if (isset($_GET['code'])) {
    $invite_code = strtoupper(trim($_GET['code']));
    
    // Get leaderboard with creator username
    $stmt = $pdo->prepare("
        SELECT l.*, u.username as creator_username 
        FROM leaderboards l
        JOIN users u ON l.creator_id = u.id
        WHERE l.invite_code = ?
    ");
    $stmt->execute([$invite_code]);
    $leaderboard = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leaderboard) {
        $error = "Invalid invite code. Please check and try again.";
    } elseif (isUserInLeaderboard($pdo, $leaderboard['id'], $user_id)) {
        header('Location: dashboard.php?page=leaderboard');
        exit;
    }
}

// Handle join submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join') {
    $leaderboard_id = (int)$_POST['leaderboard_id'];
    $source_type = $_POST['source_type']; // 'counter' or 'group'
    $source_id = (int)$_POST['source_id'];
    
    $counter_id = $source_type === 'counter' ? $source_id : null;
    $group_id = $source_type === 'group' ? $source_id : null;
    
    // Verify ownership and join
    try {
        if ($counter_id) {
            $stmt = $pdo->prepare("SELECT id FROM counters WHERE id = ? AND user_id = ?");
            $stmt->execute([$counter_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM counter_groups WHERE id = ? AND user_id = ?");
            $stmt->execute([$group_id, $user_id]);
        }
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO leaderboard_members (leaderboard_id, user_id, counter_id, group_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$leaderboard_id, $user_id, $counter_id, $group_id]);
            
            header('Location: dashboard.php?page=leaderboard');
            exit;
        } else {
            $error = "Invalid counter or group selected.";
        }
    } catch (PDOException $e) {
        $error = "Error joining leaderboard. Please try again.";
    }
}

// Get user's counters and groups
$userCounters = getUserCounters($pdo, $user_id);
$userGroups = getUserGroups($pdo, $user_id);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Leaderboard - TopItUp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #000000;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .join-container {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            border: 1px solid #2a2a2a;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        .join-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .join-header h1 {
            color: #ffffff;
            margin: 10px 0;
        }
        .error-box {
            background: rgba(244, 67, 54, 0.1);
            border-left: 4px solid #f44336;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #ff6b6b;
        }
        .leaderboard-info {
            background: #262626;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #2a2a2a;
        }
        .leaderboard-info h3 {
            color: #ffffff;
            margin: 0 0 10px 0;
        }
        .leaderboard-info p {
            color: #a0a0a0;
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ffffff;
        }
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #2a2a2a;
            border-radius: 8px;
            font-size: 14px;
            background: #0a0a0a;
            color: #ffffff;
            transition: border-color 0.3s ease;
        }
        .form-group select:focus {
            border-color: #667eea;
            outline: none;
            background: #1a1a1a;
        }
        .btn-join {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="join-container">
        <div class="join-header">
            <i class="fas fa-trophy" style="font-size: 48px; color: #667eea;"></i>
            <h1>Join Leaderboard</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($leaderboard): ?>
            <div class="leaderboard-info">
                <h3><?= htmlspecialchars($leaderboard['name']) ?></h3>
                <p style="color: #666; font-size: 14px;">
                    Created by <strong><?= htmlspecialchars($leaderboard['creator_username']) ?></strong>
                </p>
            </div>
            
            <form method="POST" onsubmit="return prepareSourceSubmit()">
                <input type="hidden" name="action" value="join">
                <input type="hidden" name="leaderboard_id" value="<?= $leaderboard['id'] ?>">
                <input type="hidden" name="source_id" id="finalSourceId" value="">
                
                <div class="form-group">
                    <label>Choose what to track:</label>
                    <select name="source_type" id="sourceType" required onchange="updateSourceOptions()">
                        <option value="">-- Select Type --</option>
                        <option value="counter">Single Counter</option>
                        <option value="group">Counter Group</option>
                    </select>
                </div>
                
                <div class="form-group" id="counterSelect" style="display: none;">
                    <label>Select Counter:</label>
                    <select id="counterOptions">
                        <?php foreach ($userCounters as $counter): ?>
                            <option value="<?= $counter['id'] ?>">
                                <?= htmlspecialchars($counter['name']) ?> (<?= $counter['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="groupSelect" style="display: none;">
                    <label>Select Group:</label>
                    <select id="groupOptions">
                        <?php foreach ($userGroups as $group): ?>
                            <option value="<?= $group['id'] ?>">
                                <?= htmlspecialchars($group['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-join">
                    <i class="fas fa-sign-in-alt"></i> Join Leaderboard
                </button>
            </form>
        <?php else: ?>
            <p style="text-align: center; color: #999;">
                No leaderboard found. Check your invite code.
            </p>
        <?php endif; ?>
    </div>
    
    <script>
    function prepareSourceSubmit() {
        const sourceType = document.getElementById('sourceType').value;
        const finalSourceId = document.getElementById('finalSourceId');
        
        if (sourceType === 'counter') {
            finalSourceId.value = document.getElementById('counterOptions').value;
        } else if (sourceType === 'group') {
            finalSourceId.value = document.getElementById('groupOptions').value;
        } else {
            alert('Please select a type');
            return false;
        }
        
        return true;
    }
    
    function updateSourceOptions() {
        const sourceType = document.getElementById('sourceType').value;
        const counterSelect = document.getElementById('counterSelect');
        const groupSelect = document.getElementById('groupSelect');
        
        if (sourceType === 'counter') {
            counterSelect.style.display = 'block';
            groupSelect.style.display = 'none';
        } else if (sourceType === 'group') {
            counterSelect.style.display = 'none';
            groupSelect.style.display = 'block';
        } else {
            counterSelect.style.display = 'none';
            groupSelect.style.display = 'none';
        }
    }
    </script>
</body>
</html>
