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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control Admin - TopItUp</title>
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
            padding: 40px 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .menu-card.email-card {
            border-top: 4px solid #ff6b6b;
        }
        .menu-card.stats-card {
            border-top: 4px solid #4ecdc4;
        }
        .menu-card.manual-card {
            border-top: 4px solid #95e1d3;
        }
        .menu-card.logout-card {
            border-top: 4px solid #ffd93d;
        }
        .menu-icon {
            font-size: 2.5em;
        }
        .menu-card h2 {
            font-size: 1.3em;
            color: #333;
            margin: 0;
        }
        .menu-card p {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }
        .logout-btn {
            background: white;
            border: 2px solid #ffd93d;
            color: #333;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: #ffd93d;
            color: white;
        }
        .footer {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 40px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎛️ Panel de Control</h1>
            <p>Hola, <strong><?php echo htmlspecialchars($username); ?></strong></p>
        </header>

        <div class="menu-grid">
            <a href="send_email.php" class="menu-card email-card">
                <span class="menu-icon">📧</span>
                <h2>Enviar Email</h2>
                <p>Redacta y envía emails personalizados</p>
            </a>

            <a href="stats.php" class="menu-card stats-card">
                <span class="menu-icon">📊</span>
                <h2>Estadísticas Auto</h2>
                <p>Información de automatización</p>
            </a>

            <a href="manual_overview.php" class="menu-card manual-card">
                <span class="menu-icon">👁️</span>
                <h2>Vista Manual</h2>
                <p>Previsualiza y envía manualmente</p>
            </a>

        
        </div>

        <div class="footer">
            <p>TopItUp Admin Panel © 2025</p>
        </div>
    </div>
</body>
</html>
