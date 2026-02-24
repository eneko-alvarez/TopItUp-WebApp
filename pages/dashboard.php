<?php
require_once 'includes/functions.php';

// Get user's filter year from cookie (default current year)
$filter_year = getUserFilterYear();

$groups = getUserGroups($pdo, $user_id);
$unassignedCounters = getUnassignedCounters($pdo, $user_id, $filter_year);

$groupsData = [];
foreach ($groups as $group) {
    $counters = getGroupCounters($pdo, $group['id'], $filter_year);
    $total = getGroupTotal($pdo, $group['id'], $filter_year);

    $countersData = [];
    foreach ($counters as $counter) {
        $percentage = $total > 0 ? round(($counter['count'] / $total) * 100, 1) : 0;
        $countersData[] = [
            'id' => $counter['id'],
            'name' => $counter['name'],
            'count' => $counter['count'],
            'color' => $counter['color'],
            'percentage' => $percentage
        ];
    }

    $groupsData[] = [
        'id' => $group['id'],
        'name' => $group['name'],
        'color' => $group['color'],
        'is_public' => $group['is_public'],
        'total' => $total,
        'counters' => $countersData
    ];
}

foreach ($unassignedCounters as &$counter) {
    $lastLog = getCounterLastLog($pdo, $counter['id']);
    $counter['last_date'] = $lastLog['date'] ?? null;
    $counter['last_hour'] = $lastLog['hour'] ?? null;
}
unset($counter);

$stmt = $pdo->prepare("
    SELECT cl.date, cl.hour, c.name AS counter_name, c.color 
    FROM counter_logs cl 
    JOIN counters c ON cl.counter_id = c.id 
    WHERE c.user_id = ? AND YEAR(cl.date) = ?
    ORDER BY cl.date DESC, cl.hour DESC 
    LIMIT 10
");
$stmt->execute([$user_id, $filter_year]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-page">
    <?php if (empty($groupsData) && empty($unassignedCounters)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <h3 style="color: #666; margin-bottom: 0.5rem;"><?= t('dashboard.empty.title') ?></h3>
            <p style="color: #999;"><?= t('dashboard.empty.message') ?></p>
        </div>
    <?php else: ?>
        <div class="groups-grid">
            <?php foreach ($groupsData as $group): ?>
                <div class="group-card clickable-card <?= $group['is_public'] == 0 ? 'collapsed' : '' ?>"
                     id="groupCard<?= $group['id'] ?>"
                     data-url="?page=increment&group_id=<?= $group['id'] ?>"
                     style="border-left-color: <?= htmlspecialchars($group['color']) ?>">
                    <div class="group-header">
                        <div class="group-name-container">
                            <i class="fas fa-chevron-<?= $group['is_public'] == 0 ? 'down' : 'up' ?> group-toggle-icon" 
                               onclick="toggleGroup(event, <?= $group['id'] ?>, this)"></i>
                            <div class="group-name"><?= htmlspecialchars($group['name']) ?></div>
                        </div>
                        <div class="group-total" style="color: <?= htmlspecialchars($group['color']) ?>">
                            <?= $group['total'] ?>
                        </div>
                    </div>

                    <div class="group-content" id="groupContent<?= $group['id'] ?>" style="display: <?= $group['is_public'] == 0 ? 'none' : 'block' ?>;">
                        <?php if (!empty($group['counters'])): ?>
                            <div class="group-breakdown">
                                <?php foreach ($group['counters'] as $counter): ?>
                                    <div class="counter-breakdown-item">
                                        <div class="counter-breakdown-info">
                                            <span class="counter-breakdown-name"><?= htmlspecialchars($counter['name']) ?></span>
                                            <span class="counter-breakdown-value">
                                                <?= $counter['count'] ?> 
                                                <span style="color: #999;">(<?= $counter['percentage'] ?>%)</span>
                                            </span>
                                        </div>
                                        <div class="counter-breakdown-bar">
                                            <div class="counter-breakdown-fill"
                                                 style="width: <?= $counter['percentage'] ?>%; background: <?= htmlspecialchars($counter['color']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-group-message"><?= t('dashboard.no_counters_assigned') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($unassignedCounters as $counter): ?>
                <div class="group-card clickable-card collapsed"
                     data-url="?page=increment&counter_id=<?= $counter['id'] ?>"
                     style="border-left-color: <?= htmlspecialchars($counter['color']) ?>">
                    <div class="group-header">
                        <div class="group-name-container">
                            <div class="group-name"><?= htmlspecialchars($counter['name']) ?></div>
                        </div>
                        <div class="group-total" style="color: <?= htmlspecialchars($counter['color']) ?>">
                            <?= $counter['count'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="history-section">
            <div class="history-header" onclick="toggleHistory()">
                <h3><i class="fas fa-history"></i> <?= t('dashboard.history.title') ?></h3>
                <i class="fas fa-chevron-down history-toggle-icon" id="historyToggleIcon"></i>
            </div>
            <div class="history-content" id="historyContent" style="display: none;">
                <?php if (empty($history)): ?>
                    <p class="empty-history"><?= t('dashboard.history.empty') ?></p>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($history as $entry): ?>
                            <div class="history-item">
                                <span class="history-counter" style="color: <?= htmlspecialchars($entry['color']) ?>">
                                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.5rem;"></i>
                                    <?= htmlspecialchars($entry['counter_name']) ?>
                                </span>
                                <span class="history-date">
                                    <?= date('d/m/Y H:i', strtotime($entry['date'] . ' ' . $entry['hour'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleGroup(event, groupId, iconElement) {
    event.stopPropagation();
    
    const card = document.getElementById('groupCard' + groupId);
    const content = document.getElementById('groupContent' + groupId);
    const isExpanded = content.style.display !== 'none';
    
    if (isExpanded) {
        content.style.display = 'none';
        iconElement.classList.remove('fa-chevron-up');
        iconElement.classList.add('fa-chevron-down');
        card.classList.add('collapsed');
    } else {
        content.style.display = 'block';
        iconElement.classList.remove('fa-chevron-down');
        iconElement.classList.add('fa-chevron-up');
        card.classList.remove('collapsed');
    }
    
    fetch('ajax_toggle_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            group_id: groupId,
            is_expanded: !isExpanded
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to toggle group:', data.error);
        }
    })
    .catch(error => console.error('Error toggling group:', error));
}

function toggleHistory() {
    const content = document.getElementById('historyContent');
    const icon = document.getElementById('historyToggleIcon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.clickable-card').forEach(card => {
        card.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            if (url) {
                window.location.href = url;
            }
        });
    });
});
</script>
