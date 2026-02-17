<?php
session_start();
require_once "../config.php";

// Verify user is logged in and is 'eneko'
$loggedIn = false;
$username = null;

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

// Check if stats have been sent this month
$currentMonth = date('Y-m'); // Format: 2026-02
$lastSendMonth = null;

$sendLogFile = __DIR__ . '/.stats_send_log';
if (file_exists($sendLogFile)) {
    $lastSendMonth = file_get_contents($sendLogFile);
}

$alreadySentThisMonth = ($lastSendMonth === $currentMonth);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas Automáticas - TopItUp Admin</title>
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
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .card h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.8em;
        }
        .info-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-section h3 {
            color: #333;
            font-size: 1em;
            margin-bottom: 10px;
        }
        .info-section p {
            color: #666;
            font-size: 0.95em;
            line-height: 1.6;
            margin: 5px 0;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .status.active {
            background: #c8e6c9;
            border-left: 4px solid #4caf50;
            color: #2e7d32;
        }
        .status.inactive {
            background: #f8bbd0;
            border-left: 4px solid #e91e63;
            color: #c2185b;
        }
        .status strong {
            display: block;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        button, a.button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            display: block;
        }
        .btn-back {
            background: #757575;
            color: white;
        }
        .btn-back:hover {
            background: #616161;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .btn-manual {
            background: #2196f3;
            color: white;
        }
        .btn-manual:hover {
            background: #1976d2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33,150,243,0.3);
        }
        .schedule-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 25px;
        }
        .schedule-info h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 0.95em;
        }
        .schedule-info p {
            color: #1565c0;
            font-size: 0.9em;
            line-height: 1.5;
            margin: 5px 0;
        }
        .schedule-info code {
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            margin-top: 5px;
        }
        .badge.auto {
            background: #4caf50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>📊 Estadísticas Automáticas</h1>

            <div class="info-section">
                <h3>🔄 Configuración Actual</h3>
                <p><strong>Envío Automático:</strong> Activado el 1º de cada mes</p>
                <p><strong>Gatillo:</strong> Se ejecuta automáticamente cuando cualquier usuario accede al dashboard</p>
                <p><strong>Período:</strong> Mes anterior completado</p>
                <p><strong>Destinatarios:</strong> Solo eneko.alvarez@opendeusto.es (TESTING)</p>
                <p style="color: #d32f2f; margin-top: 10px;"><strong>⚠️ Modo Testing Activo</strong> - Cambiar a todos los usuarios cuando termines pruebas</p>
            </div>

            <?php if ($alreadySentThisMonth): ?>
                <div class="status active">
                    <strong>✓ Ya Enviado Este Mes</strong>
                    Las estadísticas ya fueron distribuidas a todos los usuarios este mes.
                    <span class="badge auto">COMPLETADO</span>
                </div>
            <?php else: ?>
                <div class="status inactive">
                    <strong>⏳ Pendiente Este Mes</strong>
                    Se enviará automáticamente cuando el próximo usuario acceda a la app
                </div>
            <?php endif; ?>

            <div class="info-section">
                <h3>⚙️ Información Técnica</h3>
                <p><strong>Mes Actual:</strong> <?php echo date('m/Y'); ?></p>
                <p><strong>Último Envío:</strong> <?php echo ($lastSendMonth ? $lastSendMonth : 'Nunca'); ?></p>
                <p><strong>Estado del Sistema:</strong> Activo y funcionando correctamente</p>
            </div>

            <div class="schedule-info">
                <h4>📅 ¿Cómo Funciona?</h4>
                <p>El sistema automático se ejecuta en el primer acceso de cualquier usuario en cada mes nuevo:</p>
                <p><strong>Primer usuario:</strong> Cuando cualquier usuario accede al dashboard en un mes nuevo, se gatilla automáticamente el envío.</p>
                <p><strong>Esto significa:</strong> Se ejecuta el primer día que alguien entre (puede ser día 1, 2, 17, etc.), una sola vez por mes.</p>
                <p><strong>Ventaja:</strong> No depende de admins, se distribuye entre todas las requests, funciona automáticamente sin CRON.</p>
                <p><strong>CRON Opcional:</strong> Si quieres garantizar ejecución a fecha/hora exacta, configura:</p>
                <p style="margin-top: 10px; color: #1565c0;"><code>0 0 1 * * curl -s https://topitup.party/admin/send_stats.php?auto=1 &gt; /dev/null 2&gt;&amp;1</code></p>
            </div>

            <div class="button-group">
                <a href="index.php" class="button btn-back">← Volver</a>
                <a href="manual_overview.php" class="button btn-manual">Envío Manual →</a>
            </div>
        </div>
    </div>
</body>
</html>
