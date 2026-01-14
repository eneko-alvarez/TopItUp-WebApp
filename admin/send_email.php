<?php
session_start();
require_once "config.php";

// Verify user is logged in using the correct session variable name
// First check if there's an active PHP session
$loggedIn = false;
$username = null;

if (isset($_SESSION['userid'])) {
    // Get username from userid
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['userid']]);
    $user = $stmt->fetch();
    if ($user) {
        $loggedIn = true;
        $username = $user['username'];
    }
}

// If not logged in, redirect to login page
if (!$loggedIn) {
    header("Location: login.php");
    exit;
}

// Verify the logged-in user is 'eneko'
if ($username !== 'eneko') {
    die('Access denied. Only user "eneko" can access this page.');
}

// Handle form submission
$success = [];
$errors = [];
$sending = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $subject = $_POST['subject'] ?? '';
    $htmlContent = $_POST['html_content'] ?? '';
    $selectedSender = $_POST['sender'] ?? 'stats';
    
    // Define sender options
    $senders = [
        'stats' => ["name" => "TopItUp Stats", "email" => "stats@topitup.party"],
        'message' => ["name" => "TopItUp", "email" => "admin@topitup.party"]
    ];
    
    $senderInfo = $senders[$selectedSender] ?? $senders['stats'];
    
    if (empty($subject) || empty($htmlContent)) {
        $errors[] = 'El asunto y el contenido son obligatorios.';
    } else {
        $sending = true;
        
        // Function to send email with Brevo
        function enviarEmailBrevo($email, $htmlContent, $subject, $senderInfo) {
            global $brevoApiKey;
            
            $url = "https://api.brevo.com/v3/smtp/email";
            
            $data = [
                "sender" => $senderInfo,
                "to" => [["email" => $email]],
                "subject" => $subject,
                "htmlContent" => $htmlContent
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "accept: application/json",
                "content-type: application/json",
                "api-key: $brevoApiKey"
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return ['error' => $error];
            }
            return json_decode($response, true);
        }
        
        // Get all users with email
        $usuariosQuery = $pdo->query("SELECT id, email, username FROM users");
        $usuarios = $usuariosQuery->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($usuarios as $usuario) {
            $email = $usuario['email'];
            $resultado = enviarEmailBrevo($email, $htmlContent, $subject, $senderInfo);
            
            if (isset($resultado['error'])) {
                $errors[] = "Error enviando a {$email}: " . $resultado['error'];
            } elseif (isset($resultado['messageId'])) {
                $success[] = "✓ Enviado correctamente a {$email}";
            } elseif (isset($resultado['code'])) {
                $errors[] = "Error API para {$email}: " . ($resultado['message'] ?? 'Unknown error');
            } else {
                $errors[] = "Respuesta inesperada para {$email}";
            }
        }
    }
}

// Get recipient count for display
$recipientCountQuery = $pdo->query("SELECT COUNT(*) as count FROM users");
$recipientCount = $recipientCountQuery->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Email Manual - TopItUp</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #0a0e27;
            color: #e0e0e0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .back-link {
            display: inline-block;
            color: #8b9dc3;
            text-decoration: none;
            margin-bottom: 30px;
            font-size: 14px;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: #fff;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff;
        }
        
        .subtitle {
            color: #8b9dc3;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .warning {
            background: rgba(255, 193, 7, 0.1);
            border-left: 3px solid #ffc107;
            padding: 12px 16px;
            margin-bottom: 30px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #fff;
        }
        
        input[type="text"], 
        textarea {
            width: 100%;
            padding: 12px 16px;
            background: #151b33;
            border: 1px solid #2a3651;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s, background 0.2s;
        }
        
        input[type="text"]:focus, 
        textarea:focus {
            outline: none;
            border-color: #667eea;
            background: #1a2140;
        }
        
        textarea {
            min-height: 250px;
            font-family: 'Courier New', monospace;
            resize: vertical;
            line-height: 1.5;
        }
        
        .help-text {
            font-size: 12px;
            color: #6b7889;
            margin-top: 6px;
        }
        
        .btn {
            width: 100%;
            background: #667eea;
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn:active {
            background: #4c5fc7;
        }
        
        .results {
            margin-top: 30px;
            padding: 20px;
            background: #151b33;
            border-radius: 6px;
            border: 1px solid #2a3651;
        }
        
        .results h3 {
            font-size: 16px;
            margin-bottom: 16px;
            color: #fff;
        }
        
        .results h4 {
            font-size: 14px;
            margin-top: 16px;
            margin-bottom: 8px;
        }
        
        .success {
            color: #4caf50;
            font-size: 13px;
            padding: 4px 0;
        }
        
        .error {
            color: #f44336;
            font-size: 13px;
            padding: 4px 0;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #2a3651;
        }
        
        .stat {
            flex: 1;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7889;
        }
        
        .radio-group {
            display: flex;
            gap: 16px;
            margin-top: 8px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-option input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        .radio-option label {
            margin: 0;
            font-weight: 400;
            cursor: pointer;
        }
        
        .recipient-info {
            text-align: center;
            margin-top: 12px;
            font-size: 13px;
            color: #8b9dc3;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Volver</a>
        
        <h1>Envío Manual de Correos</h1>
        <p class="subtitle">Envía un mensaje a todos los usuarios registrados</p>
        
        <div class="warning">
            <strong>⚠️ Atención:</strong> Este correo se enviará a todos los usuarios de la base de datos.
        </div>
        
        <form method="POST" onsubmit="return confirm('¿Confirmas que quieres enviar este email a <?= $recipientCount ?> usuario(s)?');">
            <div class="form-group">
                <label>Remitente</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="sender_stats" name="sender" value="stats" checked>
                        <label for="sender_stats">stats@topitup.party</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="sender_message" name="sender" value="message">
                        <label for="sender_message">admin@topitup.party</label>
                    </div>
                </div>
                <div class="help-text">Selecciona el remitente del correo</div>
            </div>
            
            <div class="form-group">
                <label for="subject">Asunto</label>
                <input type="text" id="subject" name="subject" required placeholder="Novedades importantes">
                <div class="help-text">El asunto que verán en su bandeja de entrada</div>
            </div>
            
            <div class="form-group">
                <label for="html_content">Contenido HTML</label>
                <textarea id="html_content" name="html_content" required placeholder="<p>Hola,</p>
<p>Tu mensaje aquí...</p>"></textarea>
                <div class="help-text">Puedes usar HTML: &lt;p&gt; para párrafos, &lt;b&gt; para negrita, &lt;a href=""&gt; para links</div>
            </div>
            
            <button type="submit" name="send_email" class="btn">Enviar correos</button>
            <div class="recipient-info">
                📬 Se enviará a <strong><?= $recipientCount ?></strong> usuario(s)
            </div>
        </form>
        
        <?php if ($sending && (count($success) > 0 || count($errors) > 0)): ?>
            <div class="results">
                <h3>Resultados del envío</h3>
                
                <div class="stats">
                    <div class="stat">
                        <div class="stat-value" style="color: #4caf50;"><?= count($success) ?></div>
                        <div class="stat-label">Exitosos</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #f44336;"><?= count($errors) ?></div>
                        <div class="stat-label">Errores</div>
                    </div>
                </div>
                
                <?php if (count($success) > 0): ?>
                    <h4 style="color: #4caf50;">✓ Enviados</h4>
                    <?php foreach ($success as $msg): ?>
                        <div class="success"><?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (count($errors) > 0): ?>
                    <h4 style="color: #f44336;">✗ Errores</h4>
                    <?php foreach ($errors as $error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
