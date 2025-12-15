<?php
require_once 'includes/functions.php';

$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'increment') {
    $counter_id = (int)($_POST['counter_id'] ?? 0);
    $return_url = $_POST['return_url'] ?? '?page=dashboard';
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    
    $stmt = $pdo->prepare("SELECT id FROM counters WHERE id = ? AND user_id = ?");
    $stmt->execute([$counter_id, $user_id]);
    
    if ($stmt->fetch()) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE counters SET count = count + 1 WHERE id = ?");
            $stmt->execute([$counter_id]);
            
            // Insert with geolocation data
            $stmt = $pdo->prepare("INSERT INTO counter_logs (counter_id, date, hour, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$counter_id, $currentDate, $currentTime, $latitude, $longitude]);
            
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
                            Never
                        <?php endif; ?>
                    </div>
                    <form method="POST" style="margin: 0;" class="increment-form">
                        <input type="hidden" name="action" value="increment">
                        <input type="hidden" name="counter_id" value="<?= (int)$counter['id'] ?>">
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($return_url) ?>">
                        <button type="submit" class="increment-btn" style="background: <?= htmlspecialchars($counter['color']) ?>">
                            <span class="btn-text">+1</span>
                            <span class="btn-loading" style="display: none;"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Recent Increments History with Delete Option -->
        <div class="increment-history-section" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #f8f9fa;">
            <h4 style="color: #495057; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-history"></i> Recent Activity
                <span style="font-size: 12px; color: #999; font-weight: normal;">(click <i class="fas fa-trash" style="font-size: 10px;"></i> to undo)</span>
            </h4>
            <?php
            // Get last 10 logs for this counter/group
            if ($counter_id) {
                $stmt = $pdo->prepare("
                    SELECT id, date, hour, latitude, longitude
                    FROM counter_logs
                    WHERE counter_id = ?
                    ORDER BY date DESC, hour DESC
                    LIMIT 10
                ");
                $stmt->execute([$counter_id]);
            } else {
                $counterIds = array_map(fn($c) => $c['id'], $countersToShow);
                $placeholders = implode(',', array_fill(0, count($counterIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT cl.id, cl.date, cl.hour, cl.latitude, cl.longitude, c.name as counter_name, c.color
                    FROM counter_logs cl
                    JOIN counters c ON cl.counter_id = c.id
                    WHERE cl.counter_id IN ($placeholders)
                    ORDER BY cl.date DESC, cl.hour DESC
                    LIMIT 10
                ");
                $stmt->execute($counterIds);
            }
            $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($recentLogs)): ?>
                <p style="color: #999; font-style: italic; text-align: center; padding: 20px;">No records yet</p>
            <?php else: ?>
                <div class="history-log-list" style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="history-log-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 3px solid <?= isset($log['counter_name']) ? htmlspecialchars($log['color']) : $pageColor ?>;">
                            <div>
                                <?php if (isset($log['counter_name'])): ?>
                                    <span style="color: <?= htmlspecialchars($log['color']) ?>; font-weight: 600; margin-right: 10px;">
                                        <?= htmlspecialchars($log['counter_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <span style="color: #6c757d; font-size: 14px;">
                                    <?= date('d/m/Y H:i', strtotime($log['date'] . ' ' . $log['hour'])) ?>
                                </span>
                                <?php if ($log['latitude'] && $log['longitude']): ?>
                                    <i class="fas fa-map-marker-alt" style="color: #28a745; margin-left: 8px; font-size: 12px;" title="With location"></i>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="delete_log.php" style="margin: 0;">
                                <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($return_url) ?>">
                                <button type="submit" class="btn-small-danger" style="padding: 6px 10px; font-size: 12px;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle +1 increment forms with geolocation
    const incrementForms = document.querySelectorAll('.increment-form');
    
    incrementForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.increment-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            // Show loading state
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            submitBtn.disabled = true;
            
            // Request geolocation - only if permission already granted
            if (navigator.geolocation && navigator.permissions) {
                navigator.permissions.query({ name: 'geolocation' }).then((result) => {
                    if (result.state === 'granted' || result.state === 'prompt') {
                        // Permission granted or pending - get location (will ask if pending)
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const latInput = document.createElement('input');
                                latInput.type = 'hidden';
                                latInput.name = 'latitude';
                                latInput.value = position.coords.latitude;
                                this.appendChild(latInput);
                                
                                const lonInput = document.createElement('input');
                                lonInput.type = 'hidden';
                                lonInput.name = 'longitude';
                                lonInput.value = position.coords.longitude;
                                this.appendChild(lonInput);
                                
                                this.submit();
                            },
                            (error) => {
                                // Error or denied, submit anyway
                                this.submit();
                            },
                            {
                                maximumAge: 60000,
                                enableHighAccuracy: false
                            }
                        );
                    } else {
                        // Permission denied, submit without location
                        this.submit();
                    }
                }).catch(() => {
                    // Permissions API error, submit without location
                    this.submit();
                });
            } else if (navigator.geolocation) {
                // Fallback for browsers without Permissions API - ask for location
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const latInput = document.createElement('input');
                        latInput.type = 'hidden';
                        latInput.name = 'latitude';
                        latInput.value = position.coords.latitude;
                        this.appendChild(latInput);
                        
                        const lonInput = document.createElement('input');
                        lonInput.type = 'hidden';
                        lonInput.name = 'longitude';
                        lonInput.value = position.coords.longitude;
                        this.appendChild(lonInput);
                        
                        this.submit();
                    },
                    (error) => {
                        this.submit();
                    },
                    {
                        maximumAge: 60000,
                        enableHighAccuracy: false
                    }
                );
            } else {
                // No geolocation support, submit anyway
                this.submit();
            }
        });
    });
    
    
    // Handle delete forms with AJAX
    setTimeout(function() {
        const deleteForms = document.querySelectorAll('form[action="delete_log.php"]');
        
        deleteForms.forEach((form) => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // TODO: Add confirmation dialog later
                
                const formData = new FormData(this);
                const returnUrl = formData.get('return_url');
                
                fetch('delete_log.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    window.location.href = returnUrl;
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Error deleting increment. Please try again.');
                });
            });
        });
    }, 500);
});
</script>