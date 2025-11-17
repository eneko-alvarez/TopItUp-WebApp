<?php
session_start();

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['userid'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';

$message = '';

// Registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $email && $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            $user_id = $pdo->lastInsertId();
            
            // Crear contadores por defecto
            $default_counters = [
                ['name' => 'Cervezas', 'color' => '#eafb93ff', 'is_public' => 1],
                ['name' => 'Cubatas', 'color' => '#4facfe', 'is_public' => 1],
                ['name' => 'Chupitos', 'color' => '#43e97b', 'is_public' => 1],
                ['name' => 'Gym', 'color' => '#fa709a', 'is_public' => 1]
            ];
            
            foreach ($default_counters as $counter) {
                $stmt = $pdo->prepare("INSERT INTO counters (user_id, name, color, is_public) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $counter['name'], $counter['color'], $counter['is_public']]);
            }
            
            $pdo->commit();
            $message = "Registration successful! Please login.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $message = "Username or email already exists.";
        }
    }
}

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["userid"] = $user["id"];
            $_SESSION["username"] = $username;
            // Generar token único
            $rememberToken = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 year'));
            // Insertar en user_sessions
            $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, token, expires_at, user_agent, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $user["id"],
                $rememberToken,
                $expiry,
                $_SERVER["HTTP_USER_AGENT"] ?? '',
                $_SERVER["REMOTE_ADDR"] ?? '',
            ]);
            setcookie("rememberuser", $rememberToken, time() + 365*24*60*60, "/");
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Invalid credentials.";
        }
    }
}
?>
<!-- El resto del HTML permanece igual -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TopItUp - Login/Register</title>
    <!-- Manifest para Android/Chromium y soporte general PWA -->
    <link rel="manifest" href="/manifest.webmanifest">

    <!-- iOS: abrir en ventana propia (standalone) y estilo de barra de estado -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="TopItUp">

    <!-- Iconos iOS (elige rutas reales de tus PNG) -->
    <link rel="apple-touch-icon" href="files/logo.png">
    <link rel="icon" type="image/png" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="167x167" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="files/logo.png">

    <!-- Colores de tema (Android splash/barra) -->
    <meta name="theme-color" content="#f4f4f4ff">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: #a6b2e9ff;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .tab.active {
            background: #667eea;
            color: white;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #5a6fd8;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            background: #dad7f8ff;
            color: #1c1d72ff;
            border: 1px solid #c6c6f5ff;
        }
        .form-content {
            display: none;
        }
        .form-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <center><img src="files/full_logo.png" alt="TopItUp Logo" style="width: 200px; height: auto;"></center><br>
        
        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="showForm('login')">Login</button>
            <button class="tab" onclick="showForm('register')">Register</button>
        </div>
        
        <!-- Login Form -->
        <div id="login-form" class="form-content active">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="login-username">Username:</label>
                    <input type="text" id="login-username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="login-password">Password:</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                
                <button type="submit">Login</button>
            </form>
        </div>
        
        <!-- Register Form -->
        <div id="register-form" class="form-content">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="register-username">Username:</label>
                    <input type="text" id="register-username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="register-email">Email:</label>
                    <input type="email" id="register-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="register-password">Password:</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                
                <button type="submit">Register</button>
            </form>
        </div>
    </div>
    
    <script>
        function showForm(formType) {
            document.querySelectorAll('.form-content').forEach(form => {
                form.classList.remove('active');
            });
            
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(formType + '-form').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
    <script>
        if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js');
        });
        }
    </script>
</body>
</html>
