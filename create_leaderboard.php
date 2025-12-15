<?php
session_start();
require_once 'config.php';
require_once 'check_session.php';
require_once 'includes/functions.php';
require_once 'includes/leaderboard_functions.php';

$user_id = $_SESSION['userid'];

// Get user's counters and groups for source selection
$userCounters = getUserCounters($pdo, $user_id);
$userGroups = getUserGroups($pdo, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_leaderboard') {
        $name = trim($_POST['name']);
        $source_type = $_POST['source_type'] ?? '';
        $source_id = isset($_POST['source_id']) ? (int)$_POST['source_id'] : 0;
        
        if (!empty($name) && !empty($source_type) && $source_id > 0) {
            try {
                $pdo->beginTransaction();
                
                // Create leaderboard with default time span (current year)
                $currentYear = date('Y');
                $startDate = "$currentYear-01-01";
                $endDate = "$currentYear-12-31";
                
                $invite_code = generateInviteCode($pdo);
                $stmt = $pdo->prepare("INSERT INTO leaderboards (name, invite_code, creator_id, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $invite_code, $user_id, $startDate, $endDate]);
                $leaderboard_id = $pdo->lastInsertId();
                
                // Add creator as member with selected source
                $counter_id = $source_type === 'counter' ? $source_id : null;
                $group_id = $source_type === 'group' ? $source_id : null;
                
                $stmt = $pdo->prepare("INSERT INTO leaderboard_members (leaderboard_id, user_id, counter_id, group_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$leaderboard_id, $user_id, $counter_id, $group_id]);
                
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
            }
        }
    }
    
    header('Location: dashboard.php?page=leaderboard');
    exit;
}

// If no POST, show form below
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Leaderboard - TopItUp</title>
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
        .create-container {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            border: 1px solid #2a2a2a;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        .create-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .create-header h1 {
            color: #ffffff;
            margin: 10px 0;
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
        .form-group input,
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
        .form-group input:focus,
        .form-group select:focus {
            border-color: #667eea;
            outline: none;
            background: #1a1a1a;
        }
        .btn-create {
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
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="create-container">
        <div class="create-header">
            <i class="fas fa-trophy" style="font-size: 48px; color: #667eea;"></i>
            <h1>Create Leaderboard</h1>
        </div>
        
        <form method="POST" onsubmit="return prepareSourceSubmit()">
            <input type="hidden" name="action" value="create_leaderboard">
            <input type="hidden" name="source_id" id="finalSourceId" value="">
            
            <div class="form-group">
                <label>Leaderboard Name:</label>
                <input type="text" name="name" placeholder="e.g., Gym Bros" required maxlength="100">
            </div>
            
            <div class="form-group">
                <label>Track:</label>
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
            
            <button type="submit" class="btn-create">
                <i class="fas fa-plus"></i> Create Leaderboard
            </button>
        </form>
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
