<?php
ob_start();
session_start();
require_once 'config.php';
date_default_timezone_set('Europe/Madrid');
require 'check_session.php';

$user_id = $_SESSION['userid'];
$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'increment', 'leaderboard', 'leaderboard_view', 'leaderboard_settings', 'settings'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

$firstLogin = 0;
$tourStep = isset($_GET['tour_step']) ? (int)$_GET['tour_step'] : 0;

try {
    $checkFirstLogin = $pdo->prepare("SELECT first_login FROM users WHERE id = ?");
    $checkFirstLogin->execute([$user_id]);
    $result = $checkFirstLogin->fetch(PDO::FETCH_ASSOC);
    $firstLogin = $result ? $result['first_login'] : 0;
} catch (PDOException $e) {
    $firstLogin = 0;
}
?>

<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('dashboard.title') ?></title>
    <link rel="icon" type="image/png" href="files/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="style.css?v=1.2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="TopItUp">
    <link rel="apple-touch-icon" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="167x167" href="files/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="files/logo.png">
    <meta name="theme-color" content="#f4f4f4ff">
    <style>
        /* Estilos personalizados del tour */
        .shepherd-element {
            max-width: 90% !important;
            width: 400px !important;
        }
        
        @media (max-width: 768px) {
            .shepherd-element {
                max-width: 85% !important;
                width: 320px !important;
            }
        }
        
        .shepherd-header {
            padding: 1rem !important;
        }
        
        .shepherd-text {
            padding: 1rem !important;
            font-size: 15px !important;
            line-height: 1.5 !important;
        }
        
        .shepherd-footer {
            padding: 0.75rem 1rem !important;
        }
        
        /* Bloquear interacción durante el tour */
        body.tour-active .content,
        body.tour-active .sidebar,
        body.tour-active .bottom-menu {
            pointer-events: none;
        }
        
        body.tour-active .shepherd-modal-overlay-container {
            pointer-events: auto;
        }
    </style>
</head>
<body <?= $firstLogin == 1 ? 'class="tour-active"' : '' ?>>
    <div id="pageLoader" class="page-loader">
        <div class="loader-spinner"></div>
    </div>

    <div class="container">
        <div class="sidebar" id="sidebar-section">
            <div class="sidebar-header">
                <h1>TopItUp</h1>
                <p><?= t('dashboard.subtitle') ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="?page=leaderboard" class="<?= $page === 'leaderboard' ? 'active' : '' ?>" id="nav-leaderboard">
                    <i class="fas fa-trophy"></i> <?= t('nav.leaderboard') ?>
                </a>
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard">
                    <i class="fas fa-home"></i> <?= t('nav.dashboard') ?>
                </a>
                <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>" id="nav-settings">
                    <i class="fas fa-cog"></i> <?= t('nav.settings') ?>
                </a>
                
                <!-- Language Switcher in Sidebar -->
                <div class="sidebar-lang-switcher">
                    <button class="sidebar-lang-btn <?= currentLang() === 'en' ? 'active' : '' ?>" onclick="switchLanguage('en')">
                        🇬🇧 EN
                    </button>
                    <button class="sidebar-lang-btn <?= currentLang() === 'es' ? 'active' : '' ?>" onclick="switchLanguage('es')">
                        🇪🇸 ES
                    </button>
                </div>
            </nav>
        </div>

        <div class="bottom-menu" id="bottom-nav">
            <a href="?page=leaderboard" class="<?= $page === 'leaderboard' ? 'active' : '' ?>" id="mobile-nav-leaderboard">
                <i class="fas fa-trophy"></i>
                <?= t('nav.leaderboard') ?>
            </a>
            <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>" id="mobile-nav-dashboard">
                <i class="fas fa-home"></i>
                <?= t('nav.dashboard') ?>
            </a>
            <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>" id="mobile-nav-settings">
                <i class="fas fa-cog"></i>
                <?= t('nav.settings') ?>
            </a>
        </div>

        <div class="content">
            <?php
            $page_file = "pages/{$page}.php";
            if (file_exists($page_file)) {
                include $page_file;
            } else {
                echo "<p>" . t('common.page_not_found') . "</p>";
            }
            ?>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            document.getElementById('pageLoader').style.display = 'none';
        });

        document.querySelectorAll('.sidebar a, .bottom-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                const isTour = <?= $firstLogin == 1 ? 'true' : 'false' ?>;
                if (!isTour) {
                    document.getElementById('pageLoader').style.display = 'flex';
                }
            });
        });

        document.addEventListener('submit', function(e) {
            document.getElementById('pageLoader').style.display = 'flex';
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js"></script>
    <script src="js/tour.js?v=1.4.1"></script>
    <script>
        <?php if ($firstLogin == 1): ?>
        const currentPage = '<?= $page ?>';
        const tourStep = <?= $tourStep ?>;
        initTour(currentPage, tourStep);
        <?php endif; ?>
    </script>
    <script>
        // Language Switcher Function
        function switchLanguage(lang) {
            const url = new URL(window.location);
            url.searchParams.set('lang', lang);
            url.searchParams.delete('page'); // Keep on same page
            url.searchParams.delete('tour_step');
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
