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
    <meta name="theme-color" content="#000000">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            background: #000000;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 16px;
            border: 1px solid #2a2a2a;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-container img {
            width: 180px;
            height: auto;
            margin-bottom: 8px;
        }
        
        .welcome-text {
            color: #a0a0a0;
            font-size: 14px;
            text-align: center;
            margin-top: 8px;
        }
        
        .form-switcher {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
            background: #0a0a0a;
            padding: 4px;
            border-radius: 12px;
            border: 1px solid #2a2a2a;
        }
        
        .switch-btn {
            flex: 1;
            padding: 12px 20px;
            background: transparent;
            color: #808080;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .switch-btn.active {
            background: #667eea;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .switch-btn:hover:not(.active) {
            color: #ffffff;
            background: #1a1a1a;
        }
        
        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(102, 126, 234, 0.1);
            color: #ffffff;
            border: 1px solid #667eea;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .form-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .form-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ffffff;
            font-size: 14px;
            letter-spacing: 0.3px;
        }
        
        input[type="text"], 
        input[type="password"], 
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #2a2a2a;
            border-radius: 10px;
            background: #0a0a0a;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
        }
        
        input[type="text"]:focus, 
        input[type="password"]:focus, 
        input[type="email"]:focus {
            border-color: #667eea;
            background: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        input::placeholder {
            color: #505050;
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 32px 24px;
            }
            
            body {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="files/full_logo.png" alt="TopItUp Logo">
            <div class="welcome-text">Track what matters to you</div>
        </div>
        
        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-switcher">
            <button class="switch-btn active" onclick="showForm('login')">Sign In</button>
            <button class="switch-btn" onclick="showForm('register')">Sign Up</button>
        </div>
        
        <!-- Login Form -->
        <div id="login-form" class="form-content active">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" name="username" placeholder="Enter your username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="submit-btn">Sign In</button>
            </form>
        </div>
        
        <!-- Register Form -->
        <div id="register-form" class="form-content">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="register-username">Username</label>
                    <input type="text" id="register-username" name="username" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" placeholder="Create a password" required>
                </div>
                
                <button type="submit" class="submit-btn">Create Account</button>
            </form>
        </div>
    </div>
    
    <script>
        function showForm(formType) {
            // Hide all forms
            document.querySelectorAll('.form-content').forEach(form => {
                form.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.switch-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected form
            document.getElementById(formType + '-form').classList.add('active');
            
            // Add active to clicked button
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
