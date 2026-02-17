<?php
session_start();
require_once "../config.php";

// Verify user is logged in and is 'eneko'
$loggedIn = false;
$username = null;

$testingMode = false;



if (isset($_SESSION['userid'])) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['userid']]);
    $user = $stmt->fetch();
    if ($user) {
        $loggedIn = true;
        $username = $user['username'];
    }
}

if (!$loggedIn || $username !== 'eneko') {
    header("Location: ../login.php");
    exit;
}

// Testing mode: Read from send_stats.php configuration
// Check if send_stats.php has testingMode enabled (by reading the file or defaulting to true for safety)

// Try to detect testing mode from send_stats.php
$send_stats_content = @file_get_contents(__DIR__ . '/send_stats.php');
if ($send_stats_content && preg_match('/\$testingMode\s*=\s*(true|false)/i', $send_stats_content, $matches)) {
    $testingMode = (strtolower($matches[1]) === 'true');
}

// Traducción manual de meses
$meses = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];

$dt = new DateTime('first day of last month');
$nombre_mes = $meses[(int)$dt->format('n')];
$primer_dia_mes_pasado = date('Y-m-01', strtotime('first day of last month'));
$ultimo_dia_mes_pasado = date('Y-m-t', strtotime('last month'));

// Function to calculate statistics for a counter
function calcularEstadisticas($pdo, $counter_id, $counter_name, $primer_dia, $ultimo_dia) {
    $queryLogs = "
        SELECT date
        FROM counter_logs
        WHERE counter_id = :cid
            AND date >= :from
            AND date <= :to
        ORDER BY date
    ";
    $stmtLogs = $pdo->prepare($queryLogs);
    $stmtLogs->execute([
        'cid' => $counter_id,
        'from' => $primer_dia,
        'to' => $ultimo_dia
    ]);
    $logs = $stmtLogs->fetchAll(PDO::FETCH_COLUMN);

    $dias = [];
    foreach ($logs as $d) $dias[$d] = ($dias[$d]??0)+1;

    $dias_con_counts = array_keys($dias);
    $dias_del_mes = [];
    $start = strtotime($primer_dia);
    $end = strtotime($ultimo_dia);
    for ($i = $start; $i <= $end; $i += 86400) {
        $dias_del_mes[] = date('Y-m-d', $i);
    }

    $racha_con = 0; $max_racha_con = 0;
    foreach ($dias_del_mes as $d) {
        $racha_con = isset($dias[$d]) ? $racha_con + 1 : 0;
        $max_racha_con = max($max_racha_con, $racha_con);
    }

    $racha_sin = 0; $max_racha_sin = 0;
    foreach ($dias_del_mes as $d) {
        $racha_sin = !isset($dias[$d]) ? $racha_sin + 1 : 0;
        $max_racha_sin = max($max_racha_sin, $racha_sin);
    }

    $mayor_dia_counts = $dias ? max($dias) : 0;
    $dia_max_counts = $mayor_dia_counts ? array_search($mayor_dia_counts, $dias) : '-';

    $dia_ultimo = $dias_con_counts ? max($dias_con_counts) : '-';
    $dia_primero = $dias_con_counts ? min($dias_con_counts) : '-';

    $total_dias_activos = count($dias_con_counts);
    $total_suma_mes = array_sum($dias);
    $dias_totales_mes = count($dias_del_mes);
    $promedio_diario_real = $dias_totales_mes ? number_format($total_suma_mes / $dias_totales_mes, 2) : '0';

    return [
        'racha_con' => $max_racha_con,
        'racha_sin' => $max_racha_sin,
        'dia_max' => $dia_max_counts,
        'mayor_dia' => $mayor_dia_counts,
        'dia_ultimo' => $dia_ultimo,
        'dia_primero' => $dia_primero,
        'dias_activos' => $total_dias_activos,
        'total_suma' => $total_suma_mes,
        'promedio_diario' => $promedio_diario_real
    ];
}

$enviando = false;
$success_message = '';
$error_message = '';

// Handle manual send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_now'])) {
    $enviando = true;
    // Redirect to send_stats.php which will handle the sending
    header("Location: send_stats.php?manual=1");
    exit;
}

// Get all users for preview
$usuariosQuery = $pdo->query("SELECT id, email, username FROM users");
$usuarios = $usuariosQuery->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Manual de Estadísticas - TopItUp Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        header p {
            color: #666;
            font-size: 14px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box p {
            color: #1976d2;
            font-size: 14px;
        }
        .stats-preview {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-section {
            border: 1px solid #e0e0e0;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .user-section h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .user-email {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .counter-stats {
            background: white;
            padding: 12px;
            border-left: 3px solid #667eea;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .counter-stats h4 {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .stat-item {
            color: #555;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 5px;
        }
        .stat-item b {
            color: #333;
        }
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: center;
        }
        button {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .btn-send {
            background: #4caf50;
            color: white;
            flex: 1;
            max-width: 200px;
        }
        .btn-send:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76,175,80,0.3);
        }
        .btn-back {
            background: #757575;
            color: white;
            flex: 1;
            max-width: 200px;
        }
        .btn-back:hover {
            background: #616161;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .no-counters {
            color: #999;
            font-size: 13px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Vista Manual de Estadísticas</h1>
            <p>Periodo: <?php echo ucfirst($nombre_mes); ?> (<?php echo $primer_dia_mes_pasado; ?> al <?php echo $ultimo_dia_mes_pasado; ?>)</p>
        </header>

        <div class="info-box">
            <p>✓ Aquí puedes ver una vista previa de las estadísticas que se enviarán a todos los usuarios. Haz clic en "Enviar Ahora" para distribuir los correos.</p>
            <?php if ($testingMode): ?>
            <p style="margin-top: 10px; color: #d32f2f;"><strong>⚠️ MODO TESTING:</strong> Solo se mostrará eneko.alvarez@opendeusto.es</p>
            <?php endif; ?>
        </div>

        <?php
        // Get users - filter by testing mode
        if ($testingMode) {
            $usuariosQuery = $pdo->query("SELECT id, email, username FROM users WHERE email = 'eneko.alvarez@opendeusto.es'");
        } else {
            $usuariosQuery = $pdo->query("SELECT id, email, username FROM users");
        }
        $usuarios = $usuariosQuery->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="stats-preview">
            <?php foreach ($usuarios as $user): ?>
                <?php
                $usuario_id = $user['id'];
                $email = $user['email'];
                $nombre_usuario = htmlspecialchars($user['username']);

                // Get counters for this user
                $query = "SELECT c.id, c.name FROM counters c WHERE c.user_id = :uid";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['uid' => $usuario_id]);
                $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="user-section">
                    <h3>👤 <?php echo htmlspecialchars($nombre_usuario); ?></h3>
                    <div class="user-email">📧 <?php echo htmlspecialchars($email); ?></div>
                    
                    <?php if (count($counters) > 0): ?>
                        <?php foreach ($counters as $counter): ?>
                            <?php
                            $stats = calcularEstadisticas($pdo, $counter['id'], $counter['name'], $primer_dia_mes_pasado, $ultimo_dia_mes_pasado);
                            ?>
                            <div class="counter-stats">
                                <h4><?php echo htmlspecialchars($counter['name']); ?></h4>
                                <div class="stat-item">🔥 Racha: <b><?php echo $stats['racha_con']; ?></b> días seguidos</div>
                                <div class="stat-item">❌ Mayor pausa: <b><?php echo $stats['racha_sin']; ?></b> días</div>
                                <div class="stat-item">📈 Mejor día: <b><?php echo $stats['dia_max']; ?></b> con <b><?php echo $stats['mayor_dia']; ?></b> registros</div>
                                <div class="stat-item">📅 Días activos: <b><?php echo $stats['dias_activos']; ?></b></div>
                                <div class="stat-item">📊 Promedio diario: <b><?php echo $stats['promedio_diario']; ?></b></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-counters">No tiene contadores</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($testingMode): ?>
        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong style="color: #856404;">⚠️ Modo Testing Activado</strong><br>
            <span style="color: #856404; font-size: 13px;">Solo se mostrará y enviará a: eneko.alvarez@opendeusto.es</span>
        </div>
        <?php endif; ?>

        <div class="button-container">
            <button class="btn-back" onclick="window.location.href='index.php'">← Volver</button>
            <form method="POST" action="send_stats.php" style="flex: 1; max-width: 200px;">
                <input type="hidden" name="testingMode" value="<?php echo $testingMode ? '1' : '0'; ?>">
                <button type="submit" name="send_now" class="btn-send" onclick="return confirm('¿Enviar estadísticas a todos los usuarios?')">✓ Enviar Ahora</button>
            </form>
        </div>
    </div>
</body>
</html>
