<?php
require_once 'includes/functions.php';

$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'increment') {
    $counter_id = (int)($_POST['counter_id'] ?? 0);
    $return_url = $_POST['return_url'] ?? '?page=dashboard';
    
    $stmt = $pdo->prepare("SELECT id FROM counters WHERE id = ? AND user_id = ?");
    $stmt->execute([$counter_id, $user_id]);
    
    if ($stmt->fetch()) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE counters SET count = count + 1 WHERE id = ?");
            $stmt->execute([$counter_id]);
            $stmt = $pdo->prepare("INSERT INTO counter_logs (counter_id, date, hour) VALUES (?, ?, ?)");
            $stmt->execute([$counter_id, $currentDate, $currentTime]);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
        }
    }
    
    header('Location: ' . $return_url);
    exit;
}

$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$counter_id = isset($_GET['counter_id']) ? (int)$_GET['counter_id'] : null;

$countersToShow = [];
$pageTitle = '';
$pageColor = '#667eea';
$return_url = '';

if ($group_id) {
    $stmt = $pdo->prepare("SELECT * FROM counter_groups WHERE id = ? AND user_id = ?");
    $stmt->execute([$group_id, $user_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($group) {
        $pageTitle = $group['name'];
        $pageColor = $group['color'];
        $return_url = "?page=increment&group_id={$group_id}";
        
        $countersToShow = getGroupCounters($pdo, $group_id);
        foreach ($countersToShow as &$counter) {
            $lastLog = getCounterLastLog($pdo, $counter['id']);
            $counter['last_date'] = $lastLog['date'] ?? null;
            $counter['last_hour'] = $lastLog['hour'] ?? null;
        }
        unset($counter);
    }
} elseif ($counter_id) {
    $stmt = $pdo->prepare("SELECT * FROM counters WHERE id = ? AND user_id = ?");
    $stmt->execute([$counter_id, $user_id]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($counter) {
        $pageTitle = $counter['name'];
        $pageColor = $counter['color'];
        $return_url = "?page=increment&counter_id={$counter_id}";
        
        $lastLog = getCounterLastLog($pdo, $counter['id']);
        $counter['last_date'] = $lastLog['date'] ?? null;
        $counter['last_hour'] = $lastLog['hour'] ?? null;
        
        $countersToShow = [$counter];
    }
}

if (empty($countersToShow)) {
    header('Location: ?page=dashboard');
    exit;
}
?>

<div class="increment-page">
    <div class="increment-back">
        <a href="?page=dashboard" class="back-link">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
    
    <div class="increment-group">
        <h3 class="increment-group-title" style="color: <?= htmlspecialchars($pageColor) ?>">
            <?= htmlspecialchars($pageTitle) ?>
        </h3>
        <div class="increment-counters-grid">
            <?php foreach ($countersToShow as $counter): ?>
                <div class="increment-counter-card" style="border-left-color: <?= htmlspecialchars($counter['color']) ?>">
                    <div class="increment-counter-name"><?= htmlspecialchars($counter['name']) ?></div>
                    <div class="increment-counter-value" style="color: <?= htmlspecialchars($counter['color']) ?>">
                        <?= $counter['count'] ?>
                    </div>
                    <div class="increment-counter-info">
                        <?php if ($counter['last_date'] && $counter['last_hour']): ?>
                            <?= date('d/m/Y H:i', strtotime($counter['last_date'] . ' ' . $counter['last_hour'])) ?>
                        <?php else: ?>
                            Nunca
                        <?php endif; ?>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="increment">
                        <input type="hidden" name="counter_id" value="<?= (int)$counter['id'] ?>">
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($return_url) ?>">
                        <button type="submit" class="increment-btn" style="background: <?= htmlspecialchars($counter['color']) ?>">
                            +1
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>