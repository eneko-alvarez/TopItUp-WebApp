<?php
session_start();

require_once 'config.php';

// Si el usuario ya tiene una sesión activa, verificar si es válida
if (isset($_SESSION['userid'])) {
    // Verificar que la sesión sea válida mediante cookie
    if (isset($_COOKIE['rememberuser'])) {
        $token = $_COOKIE['rememberuser'];
        $stmt = $pdo->prepare("SELECT * FROM user_sessions WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Sesión válida, redirigir al dashboard
            header('Location: dashboard.php');
            exit;
        }
    }
    // Si no hay cookie o es inválida, limpiar sesión
    session_destroy();
    session_start();
}

$message = '';
$active_form = $_GET['form'] ?? 'login';

// Recuperar mensaje de error de sesión si existe
if (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Recuperar datos del formulario si hay un error
$form_username = $_SESSION['form_username'] ?? '';
$form_email = $_SESSION['form_email'] ?? '';
unset($_SESSION['form_username']);
unset($_SESSION['form_email']);

// Registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $message = t('auth.errors.passwords_mismatch');
        $_SESSION['error_message'] = $message;
        $_SESSION['form_username'] = $username;
        $_SESSION['form_email'] = $email;
        header('Location: login.php?form=register');
        exit;
    } elseif (strlen($password) < 8) {
        $message = t('auth.errors.password_min_length');
        $_SESSION['error_message'] = $message;
        $_SESSION['form_username'] = $username;
        $_SESSION['form_email'] = $email;
        header('Location: login.php?form=register');
        exit;
    } elseif ($username && $email && $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            $user_id = $pdo->lastInsertId();
            
            // Crear grupo "Cubatas" con sus contadores
            $stmt = $pdo->prepare("INSERT INTO counter_groups (user_id, name, color, is_public) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user_id, 'Cubatas', '#4facfe']);
            $group_id = $pdo->lastInsertId();
            
            // Contadores para el grupo Cubatas con valores random
            $cubatas_counters = [
                ['name' => 'Roncola', 'color' => '#ff6b6b'],
                ['name' => 'Gintonic', 'color' => '#4ecdc4'],
                ['name' => 'Vodka-limon', 'color' => '#ffe66d']
            ];
            
            foreach ($cubatas_counters as $counter_data) {
                // Crear contador con valor random entre 1-20
                $random_value = rand(1, 20);
                $stmt = $pdo->prepare("INSERT INTO counters (user_id, name, color, is_public, count) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$user_id, $counter_data['name'], $counter_data['color'], $random_value]);
                $counter_id = $pdo->lastInsertId();
                
                // Asignar contador al grupo
                $stmt = $pdo->prepare("INSERT INTO group_counters (group_id, counter_id) VALUES (?, ?)");
                $stmt->execute([$group_id, $counter_id]);
            }
            
            // Contadores individuales con valores random
            $individual_counters = [
                ['name' => 'Cervezas', 'color' => '#eafb93ff'],
                ['name' => 'Gym', 'color' => '#fa709a']
            ];
            
            foreach ($individual_counters as $counter_data) {
                $random_value = rand(1, 20);
                $stmt = $pdo->prepare("INSERT INTO counters (user_id, name, color, is_public, count) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$user_id, $counter_data['name'], $counter_data['color'], $random_value]);
            }
            
            $pdo->commit();
            
            // Auto-login después de registro exitoso
            $_SESSION["userid"] = $user_id;
            $_SESSION["username"] = $username;
            
            // Generar token único para remember me
            $rememberToken = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 year'));
            
            // Insertar en user_sessions
            $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, token, expires_at, user_agent, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $rememberToken,
                $expiry,
                $_SERVER["HTTP_USER_AGENT"] ?? '',
                $_SERVER["REMOTE_ADDR"] ?? '',
            ]);
            
            // Cookie con SameSite para mejor compatibilidad PWA
            setcookie("rememberuser", $rememberToken, [
                'expires' => time() + (365 * 24 * 60 * 60),
                'path' => '/',
                'domain' => '', // Dominio actual
                'secure' => true, // CRÍTICO para HTTPS
                'httponly' => true, // Seguridad extra
                'samesite' => 'Lax'
            ]);
            
            header("Location: dashboard.php");
            exit;
        } catch(PDOException $e) {
            $pdo->rollBack();
            $message = t('auth.errors.username_exists');
            $_SESSION['error_message'] = $message;
            $_SESSION['form_username'] = $username;
            $_SESSION['form_email'] = $email;
            header('Location: login.php?form=register');
            exit;
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
            
            // Cookie con SameSite para mejor compatibilidad PWA
            setcookie("rememberuser", $rememberToken, [
                'expires' => time() + (365 * 24 * 60 * 60),
                'path' => '/',
                'domain' => '', // Dominio actual
                'secure' => true, // CRÍTICO para HTTPS
                'httponly' => true, // Seguridad extra
                'samesite' => 'Lax'
            ]);
            header("Location: dashboard.php");
            exit;
        } else {
            $message = t('auth.errors.invalid_credentials');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('meta.title') ?></title>
    <meta name="description" content="<?= t('meta.description') ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="files/logo.png">
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
        
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #808080;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .password-toggle svg {
            width: 20px;
            height: 20px;
        }
        
        .username-status {
            display: flex;
            align-items: center;
            margin-top: 6px;
            font-size: 13px;
            min-height: 20px;
        }
        
        .username-status.checking {
            color: #808080;
        }
        
        .username-status.available {
            color: #43e97b;
        }
        
        .username-status.unavailable {
            color: #fa709a;
        }
        
        .username-status-icon {
            width: 16px;
            height: 16px;
            margin-right: 6px;
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
        
        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            padding-right: 48px;
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
        
        /* Language Switcher */
        .lang-switcher {
            position: fixed;
            top: 45px;
            right: 15px;
            z-index: 10001;
            display: flex;
            gap: 4px;
            background: #111;
            border-radius: 50px;
            padding: 3px;
            border: 1px solid #333;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .lang-btn {
            padding: 6px 10px;
            background: transparent;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .lang-btn.active {
            background: #667eea;
            color: #fff;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .lang-btn:hover:not(.active) {
            color: #fff;
            background: #1a1a1a;
        }
        
        @media (max-width: 480px) {
            .lang-switcher {
                top: 10px;
                right: 10px;
                padding: 2px;
                gap: 2px;
            }
            
            .lang-btn {
                padding: 5px 8px;
                font-size: 11px;
                gap: 3px;
            }
        }
    </style>
</head>
<body>
    <!-- Language Switcher -->
    <div class="lang-switcher" id="langSwitcher">
        <button class="lang-btn <?= currentLang() === 'en' ? 'active' : '' ?>" onclick="switchLanguage('en')">
            🇬🇧 EN
        </button>
        <button class="lang-btn <?= currentLang() === 'es' ? 'active' : '' ?>" onclick="switchLanguage('es')">
            🇪🇸 ES
        </button>
    </div>
    
    <div class="container">
        <div class="logo-container">
            <img src="files/full_logo.png?v=1.1" alt="TopItUp Logo">
            <div class="welcome-text"><?= t('auth.welcome_text') ?></div>
        </div>
        
        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-switcher">
            <button class="switch-btn <?php echo $active_form === 'login' ? 'active' : ''; ?>" onclick="showForm('login')"><?= t('auth.login.title') ?></button>
            <button class="switch-btn <?php echo $active_form === 'register' ? 'active' : ''; ?>" onclick="showForm('register')"><?= t('auth.register.title') ?></button>
        </div>
        
        <!-- Login Form -->
        <div id="login-form" class="form-content <?php echo $active_form === 'login' ? 'active' : ''; ?>">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="login-username"><?= t('auth.login.username') ?></label>
                    <input type="text" id="login-username" name="username" placeholder="<?= t('auth.login.username_placeholder') ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="login-password"><?= t('auth.login.password') ?></label>
                    <div class="password-wrapper">
                        <input type="password" id="login-password" name="password" placeholder="<?= t('auth.login.password_placeholder') ?>" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('login-password')">
                            <svg id="eye-icon-login-password" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn"><?= t('auth.login.submit') ?></button>
            </form>
        </div>
        
        <!-- Register Form -->
        <div id="register-form" class="form-content <?php echo $active_form === 'register' ? 'active' : ''; ?>">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="register-username"><?= t('auth.register.username') ?></label>
                    <input type="text" id="register-username" name="username" placeholder="<?= t('auth.register.username_placeholder') ?>" value="<?php echo htmlspecialchars($form_username); ?>" required autocomplete="off">
                    <div id="username-status" class="username-status"></div>
                </div>
                
                <div class="form-group">
                    <label for="register-email"><?= t('auth.register.email') ?></label>
                    <input type="email" id="register-email" name="email" placeholder="<?= t('auth.register.email_placeholder') ?>" value="<?php echo htmlspecialchars($form_email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="register-password"><?= t('auth.register.password') ?></label>
                    <div class="password-wrapper">
                        <input type="password" id="register-password" name="password" placeholder="<?= t('auth.register.password_placeholder') ?>" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('register-password')">
                            <svg id="eye-icon-register-password" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="register-confirm-password"><?= t('auth.register.confirm_password') ?></label>
                    <div class="password-wrapper">
                        <input type="password" id="register-confirm-password" name="confirm_password" placeholder="<?= t('auth.register.confirm_password_placeholder') ?>" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('register-confirm-password')">
                            <svg id="eye-icon-register-confirm-password" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn"><?= t('auth.register.submit') ?></button>
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
        
        // Username availability checker
        let usernameTimeout;
        const usernameInput = document.getElementById('register-username');
        const usernameStatus = document.getElementById('username-status');
        
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                clearTimeout(usernameTimeout);
                const username = this.value.trim();
                
                if (username.length === 0) {
                    usernameStatus.innerHTML = '';
                    usernameStatus.className = 'username-status';
                    return;
                }
                
                if (username.length < 3) {
                    usernameStatus.innerHTML = `
                        <svg class="username-status-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= t('auth.register.username_check.min_length') ?></span>
                    `;
                    usernameStatus.className = 'username-status unavailable';
                    return;
                }
                
                // Show checking status
                usernameStatus.innerHTML = `
                    <svg class="username-status-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span><?= t('auth.register.username_check.checking') ?></span>
                `;
                usernameStatus.className = 'username-status checking';
                
                // Debounce the check
                usernameTimeout = setTimeout(() => {
                    checkUsername(username);
                }, 500);
            });
        }
        
        function checkUsername(username) {
            fetch('check_username.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + encodeURIComponent(username)
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    usernameStatus.innerHTML = `
                        <svg class="username-status-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>${data.message}</span>
                    `;
                    usernameStatus.className = 'username-status available';
                } else {
                    usernameStatus.innerHTML = `
                        <svg class="username-status-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>${data.message}</span>
                    `;
                    usernameStatus.className = 'username-status unavailable';
                }
            })
            .catch(error => {
                console.error('Error checking username:', error);
            });
        }
        
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById('eye-icon-' + inputId);
            
            if (input.type === 'password') {
                input.type = 'text';
                // Change to eye-slash icon
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                `;
            } else {
                input.type = 'password';
                // Change back to eye icon
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        }
    </script>
    <script>
        // Language Switcher Function
        function switchLanguage(lang) {
            // Reload page with lang parameter
            const url = new URL(window.location);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
