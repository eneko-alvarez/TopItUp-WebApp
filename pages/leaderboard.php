<?php
require_once 'includes/functions.php';

// Obtener todos los contadores públicos del usuario
$stmt = $pdo->prepare("SELECT id, name, color FROM counters WHERE user_id = ? AND is_public = 1 ORDER BY name ASC");
$stmt->execute([$user_id]);
$userPublicCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedCounterId = null;
$selectedCounterName = null;
$selectedCounterColor = '#667eea';

if (!empty($userPublicCounters)) {
    if (isset($_GET['counter'])) {
        foreach ($userPublicCounters as $c) {
            if ($c['name'] === $_GET['counter']) {
                $selectedCounterId = $c['id'];
                $selectedCounterName = $c['name'];
                $selectedCounterColor = $c['color'];
                break;
            }
        }
    }
    
    if (!$selectedCounterId) {
        $selectedCounterId = $userPublicCounters[0]['id'];
        $selectedCounterName = $userPublicCounters[0]['name'];
        $selectedCounterColor = $userPublicCounters[0]['color'];
    }
}

$rankings = [];
if ($selectedCounterName) {
    // Obtener todos los contadores públicos con el mismo nombre
    $stmt = $pdo->prepare("
        SELECT c.id, c.count, c.user_id, u.username
        FROM counters c
        JOIN users u ON c.user_id = u.id
        WHERE c.name = ? AND c.is_public = 1
        ORDER BY c.count DESC
    ");
    $stmt->execute([$selectedCounterName]);
    $publicCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($publicCounters as $counter) {
        $rankings[] = [
            'username' => $counter['username'],
            'total' => $counter['count']
        ];
    }
}
?>

<div class="leaderboard-page">
    <?php if (empty($userPublicCounters)): ?>
        <div class="empty-state">
            <i class="fas fa-trophy" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <h3 style="color: #666; margin-bottom: 0.5rem;">No tienes contadores públicos</h3>
            <p style="color: #999;">Crea contadores y márcalos como públicos en Ajustes para competir en el leaderboard.</p>
        </div>
    <?php else: ?>
        <div class="leaderboard-selector-card">
            <form method="GET" class="leaderboard-selector-form">
                <input type="hidden" name="page" value="leaderboard">
                <label for="counter_select"><i class="fas fa-filter"></i> Selecciona un contador:</label>
                <select name="counter" id="counter_select" onchange="this.form.submit()" class="select-leaderboard">
                    <?php foreach ($userPublicCounters as $counter): ?>
                        <option value="<?= htmlspecialchars($counter['name']) ?>" 
                                <?= $counter['name'] === $selectedCounterName ? 'selected' : '' ?>>
                            <?= htmlspecialchars($counter['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <div class="leaderboard-table">
            <?php if (!empty($rankings)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Usuario</th>
                            <th style="color: <?= htmlspecialchars($selectedCounterColor) ?>">
                                <?= htmlspecialchars($selectedCounterName) ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rankings as $index => $rank): ?>
                            <tr <?= $_SESSION['username'] === $rank['username'] ? 'class="current-user"' : '' ?>>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($rank['username']) ?></td>
                                <td><strong><?= $rank['total'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data-message">No hay otros usuarios con este contador.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
