<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user data with password hash
$stmt = $pdo->prepare("SELECT users.username, users.password, xtream_codes.* 
                      FROM users 
                      JOIN xtream_codes ON users.id = xtream_codes.user_id 
                      WHERE users.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$data = $stmt->fetch();

// Generate URLs
$originalUrl = 'http://nyama.fun/playlist.m3u?username=' . urlencode($data['xtream_username']) . '&password=' . urlencode($data['xtream_password']);
$shortUrl = @file_get_contents('https://is.gd/create.php?format=simple&url=' . urlencode($originalUrl));
if (!$shortUrl) {
    $shortUrl = $originalUrl; // Fallback to original URL if shortening fails
}

// Check if user is currently watching based on sessions.json
$isWatching = false;
$watchingInfo = null;
$sessions_file = 'sessions.json';

if (file_exists($sessions_file)) {
    $sessions = json_decode(file_get_contents($sessions_file), true);
    $currentTime = time();
    
    // Create a unique identifier for this user's playlist (same as in auth_playlist.php)
    $accountKey = md5($data['xtream_username'] . ':' . $data['xtream_password']);
    
    // Check if this user has an active session
    if (isset($sessions[$accountKey])) {
        $session = $sessions[$accountKey];
        
        // Check if session was active in the last 120 seconds (matching auth_playlist.php timeout)
        if ($currentTime - $session['last_seen'] <= 31556926) { //change big number to 120 if needed
            $isWatching = true;
            $watchingInfo = $session;
        } else {
            // Session has expired, remove it from the sessions file
            unset($sessions[$accountKey]);
            file_put_contents($sessions_file, json_encode($sessions, JSON_PRETTY_PRINT));
        }
    }
}

// Get total registered users count
$totalUsers = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    $totalUsers = $result['count'];
} catch (Exception $e) {
    // Silently fail - we don't want to break the dashboard page
    $totalUsers = "N/A";
}

// Get active viewers from sessions.json
$activeViewers = 0;
try {
    if (file_exists('sessions.json')) {
        $sessionsData = file_get_contents('sessions.json');
        $sessions = json_decode($sessionsData, true);
        if (is_array($sessions)) {
            $activeViewers = count($sessions);
        }
    }
} catch (Exception $e) {
    // Silently fail - we don't want to break the dashboard page
    $activeViewers = "N/A";
}

// Handle Change Device request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_device') {
    // Verify password
    if (!isset($_POST['password'])) {
        $_SESSION['error'] = 'Password is required!';
        header("Location: dashboard.php");
        exit();
    }
    
    if (!password_verify($_POST['password'], $data['password'])) {
        $_SESSION['error'] = 'Грешна парола!';
        header("Location: dashboard.php");
        exit();
    }

    // Remove device binding for the old credentials
    $sessions_file = 'sessions.json';
    if (file_exists($sessions_file)) {
        $sessions = json_decode(file_get_contents($sessions_file), true);
        
        // Create the old account key
        $oldAccountKey = md5($data['xtream_username'] . ':' . $data['xtream_password']);
        
        // Remove the old session
        if (isset($sessions[$oldAccountKey])) {
            unset($sessions[$oldAccountKey]);
            file_put_contents($sessions_file, json_encode($sessions, JSON_PRETTY_PRINT));
        }
    }

    // Generate new credentials to create a new URL
    $newPassword = bin2hex(random_bytes(16));
    $updateStmt = $pdo->prepare("UPDATE xtream_codes SET xtream_password = ? WHERE user_id = ?");
    $updateStmt->execute([$newPassword, $_SESSION['user_id']]);

    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --dark-bg: #0d1117;
            --darker-bg: #010409;
            --dark-border: #30363d;
            --dark-text: #e6edf3;
            --accent-blue: #2f81f7;
            --github-gray: #8b949e;
        }

        body {
            background-color: var(--darker-bg);
            color: var(--dark-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container.py-5 {
            flex: 1 0 auto;
        }

        .card {
            background-color: var(--dark-bg);
            border: 1px solid var(--dark-border);
        }

        .card-header {
            background-color: var(--dark-bg) !important;
            border-bottom: 1px solid var(--dark-border);
        }
        
        .card-header h4 {
            color: #ffffff !important;
        }

        .alert-info {
            background-color: #13233a;
            border-color: #1c2d41;
            color: var(--dark-text);
        }

        .alert-warning {
            background-color: #3b2300;
            border-color: #4d2e00;
            color: #f0b72f;
        }

        .alert-success {
            background-color: #1c532c;
            border-color: #2a5938;
            color: #2ea043;
        }

        .text-muted {
            color: var(--github-gray) !important;
        }

        .btn-outline-primary {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
        }

        .btn-outline-primary:hover {
            background-color: var(--accent-blue);
            color: white;
        }

        .copy-btn { 
            transition: all 0.3s ease; 
            padding: 2px 8px; 
            cursor: pointer; 
        }
        .copy-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 2px 6px rgba(0,0,0,0.1); 
        }
        .copied-alert { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            z-index: 1000; 
            animation: slideIn 0.3s, fadeOut 0.5s 2s; 
            min-width: 250px; 
            background-color: #1c532c;
            border-color: #2a5938;
        }
        @keyframes slideIn { from { right: -300px; } to { right: 20px; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        .font-monospace { 
            user-select: all; 
            color: #58a6ff;
        }
        .url-section { 
            border-left: 3px solid var(--accent-blue); 
            padding-left: 1rem; 
            margin: 1rem 0; 
        }
        .url-title {
            font-size: 0.9em;
            color: var(--github-gray);
            margin-bottom: 0.5rem;
        }
        @media (max-width: 576px) {
            .url-flex-container {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .copy-btn {
                margin-left: 0 !important;
                margin-top: 0.5rem;
                width: 100%;
            }
        }

        .font-monospace { 
            user-select: all; 
            color: #58a6ff;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            hyphens: auto;
            max-width: 100%;
        }

        .url-section { 
            border-left: 3px solid var(--accent-blue); 
            padding-left: 1rem; 
            margin: 1rem 0; 
        }
        .security-note {
            background: rgba(47, 129, 247, 0.1);
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 10px;
            font-size: 0.85rem;
        }
        
        .watching-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .watching-now {
            background-color: rgba(240, 183, 47, 0.2);
            color: #f0b72f;
            border: 1px solid rgba(240, 183, 47, 0.3);
        }
        
        .not-watching {
            background-color: rgba(46, 160, 67, 0.2);
            color: #2ea043;
            border: 1px solid rgba(46, 160, 67, 0.3);
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .watching-dot {
            background-color: #f0b72f;
            box-shadow: 0 0 6px rgba(240, 183, 47, 0.5);
        }
        
        .not-watching-dot {
            background-color: #2ea043;
            box-shadow: 0 0 6px rgba(46, 160, 67, 0.5);
        }
        
        .watching-info {
            margin-top: 10px;
            padding: 8px;
            background: rgba(240, 183, 47, 0.1);
            border-radius: 4px;
            font-size: 0.85rem;
            color: #e6edf3 !important;
        }
        
        .watching-info strong {
            color: #ffffff !important;
        }
        
        .change-device-form {
            margin-top: 20px;
            padding: 15px;
            background: rgba(47, 129, 247, 0.1);
            border-radius: 4px;
            color: #e6edf3 !important; /* White text for the form */
        }
        
        .change-device-form h5 {
            color: #ffffff !important; /* White text for the heading */
        }
        
        .change-device-form .form-control {
            max-width: 250px; /* Smaller input box */
        }
        
        /* ASCII Art Styling */
        .ascii-art {
            font-family: monospace;
            white-space: pre;
            color: white;
            text-align: center;
            font-size: 10px;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 0 10px rgba(255,255,255,0.3);
        }
        @media (min-width: 768px) {
            .ascii-art {
                font-size: 12px;
            }
        }
        
        /* Footer Styles */
        .footer {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px 0;
            margin-top: auto;
            backdrop-filter: blur(5px);
            border-top: 1px solid var(--dark-border);
        }
        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .footer-logo {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: #fff;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
        }
        .footer-text {
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
        .footer-bulgaria {
            font-weight: bold;
            color: #ffeb3b;
            text-shadow: 0 0 3px rgba(255, 235, 59, 0.5);
        }
        .footer-rights {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        .terms-link {
            background-color: white;
            color: black !important;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .terms-link:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* User Stats Styles */
        .user-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            border-radius: 15px;
        }
        .stat-icon {
            font-size: 14px;
        }
        .stat-value {
            font-weight: bold;
            color: #ffeb3b;
        }
        
        /* Modal Styles */
        .modal-content {
            background-color: var(--dark-bg);
            color: var(--dark-text);
            border: 1px solid var(--dark-border);
        }
        .modal-header {
            border-bottom: 1px solid var(--dark-border);
        }
        .modal-footer {
            border-top: 1px solid var(--dark-border);
        }
        .instruction-img {
            cursor: pointer;
            transition: transform 0.3s;
            border: 1px solid var(--dark-border);
            border-radius: 5px;
        }
        .instruction-img:hover {
            transform: scale(1.02);
        }
        .instruction-label {
            text-align: center;
            margin-top: 5px;
            font-size: 0.9rem;
            color: var(--github-gray);
        }
        .img-zoomed {
            max-width: 100%;
            border-radius: 5px;
            border: 1px solid var(--dark-border);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <!-- ASCII Art Header -->
                <div class="ascii-art">
    )      )            *              (              ) 
 ( /(   ( /(   (      (  `     (       )\ )        ( /( 
 )\())  )\())  )\     )\))(    )\     (()/(    (   )\())
((_)\  ((_)\((((_)(  ((_)()\((((_)(    /(_))   )\ ((_)\ 
 _((_)__ ((_))\ _ )\ (_()((_))\ _ )\  (_))_|_ ((_) _((_)
| \| |\ \ / /(_)_\(_)|  \/  |(_)_\(_) | |_ | | | || \| |
| .` | \ V /  / _ \  | |\/| | / _ \   | __|| |_| || .` |
|_|\_|  |_|  /_/ \_\ |_|  |_|/_/ \_\  |_|   \___/ |_|\_|

Няма пълно щастие !
                </div>
                
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0">Здравей, <?= htmlspecialchars($data['username']) ?>!
                            <span class="watching-status <?= $isWatching ? 'watching-now' : 'not-watching' ?>">
                                <span class="status-dot <?= $isWatching ? 'watching-dot' : 'not-watching-dot' ?>"></span>
                                <?= $isWatching ? 'Заключен към устройство' : 'Не е заключен към устройство' ?>
                            </span>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($isWatching && $watchingInfo): ?>
                        <div class="watching-info">
                            <i class="fas fa-tv me-1"></i>
                            <strong>Активна сесия:</strong><br>
                            Устройство: <?= htmlspecialchars($watchingInfo['ua']) ?><br>
                            <!--IP адрес: <?= htmlspecialchars($watchingInfo['ip']) ?><br>-->
                            Заключен към устройство на: <?= date('d-m-Y H:i:s', $watchingInfo['created']) ?><br>
                            Последна активност: <?= date('d-m-Y H:i:s', $watchingInfo['last_seen']) ?><br>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info mt-3">
                            <h5 class="alert-heading">Детайли за акаунта:</h5>
                            <div class="text-muted">Използвай линковете в любимия ти IPTV плеър и се наслаждавай на безплатна българска телевизия.
                            Един акаунт има право да ползва телевизията само на едно устройство !
                            <br>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#instructionsModal" class="text-primary">Инструкции за ползване на линковете</a>
                            </div>
                            <hr>

                            <!-- M3U Playlist URL Section -->
                            <div class="url-section">
                                <h6 class="mb-2"><i class="fas fa-list me-2"></i>Плейлист M3U URL</h6>
                                
                                <div class="mb-3">
                                    <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-center url-flex-container">
                                        <div class="font-monospace me-md-2 mb-md-0 mb-2">
                                            <?= htmlspecialchars($shortUrl) ?>
                                        </div>
                                        <button class="btn copy-btn btn-outline-primary ms-md-2" 
                                                data-value="<?= htmlspecialchars($shortUrl) ?>"
                                                onclick="copyCredentials(this)">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- EPG Guide URL Section -->
                            <div class="url-section">
                                <h6 class="mb-2"><i class="fas fa-tv me-2"></i>EPG програма URL</h6>
                                <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-center url-flex-container">
                                    <div class="font-monospace me-md-2 mb-md-0 mb-2">
                                        https://is.gd/tvbgepg
                                    </div>
                                    <button class="btn copy-btn btn-outline-primary ms-md-2" 
                                            data-value="https://is.gd/tvbgepg"
                                            onclick="copyCredentials(this)">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                                <div class="security-note text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Всички линкове са проверени и криптирани.
                                </div>
                            </div>
                        </div>

                        <!-- Change Device Form - Only show when account is locked to a device -->
                        <?php if ($isWatching): ?>
                        <div class="change-device-form">
                            <h5><i class="fas fa-sync-alt me-2"></i>Смяна на устройство</h5>
                            <p class="text-muted">Ако искаш да смениш устройството, от което гледаш, въведи паролата на твоя акаунт и натисни бутона отдолу.</p>
                            <form method="POST" action="dashboard.php">
                                <input type="hidden" name="action" value="change_device">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Парола</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-sync-alt me-2"></i>Смени устройство
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Изход
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions Modal -->
    <div class="modal fade" id="instructionsModal" tabindex="-1" aria-labelledby="instructionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="instructionsModalLabel">Инструкции за ползване на линковете</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Nyama Fun е IP телевизия, която работи с интернет без значение жичен или безжичен. За да гледате от платформата е нужно само да имате смарт устройство с предварително изтеглен IPTV плейър(Kodi, TiviMate, IPTV Smarters, Televizo и др.)</p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <img src="thumbs/kodi.jpg" class="img-fluid instruction-img" data-bs-toggle="modal" data-bs-target="#imageZoomModal" onclick="setZoomedImage(this.src)">
                            <div class="instruction-label">Меню за въвеждане на M3U URL</div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <img src="thumbs/kodi2.jpg" class="img-fluid instruction-img" data-bs-toggle="modal" data-bs-target="#imageZoomModal" onclick="setZoomedImage(this.src)">
                            <div class="instruction-label">Меню за въвеждане на EPG URL</div>
                        </div>
                    </div>
                    
                    <p>за KODI е нужно да имате IPTV Simple client и в него въвеждате линковете за TV и EPG</p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <img src="thumbs/smarters.jpg" class="img-fluid instruction-img" data-bs-toggle="modal" data-bs-target="#imageZoomModal" onclick="setZoomedImage(this.src)">
                            <div class="instruction-label">Пример за настройка под IPTV Smarters</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <img src="thumbs/tivi1.png" class="img-fluid instruction-img" data-bs-toggle="modal" data-bs-target="#imageZoomModal" onclick="setZoomedImage(this.src)">
                        </div>
                        <div class="col-md-6 mb-2">
                            <img src="thumbs/tivi2.png" class="img-fluid instruction-img" data-bs-toggle="modal" data-bs-target="#imageZoomModal" onclick="setZoomedImage(this.src)">
                        </div>
                        <div class="instruction-label text-center">Пример за настройка под TiviMate</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <img src="thumbs/tel1.png" class="img-fluid instruction-img" data-bs-toggle="modal" data-bs-target="#imageZoomModal" onclick="setZoomedImage(this.src)">
                        </div>
                        <div class="col-md-6 mb-2">
                            <img src="thumbs/tel2.png" class="img-fluid instruction-img" data-bs-toggle="modal" data-bs-target="#imageZoomModal" onclick="setZoomedImage(this.src)">
                        </div>
                        <div class="instruction-label text-center">Пример за настройка под Televizo</div>
                    </div>
                    
                    <div class="alert alert-danger mt-3">
                        Имайте впредвид, че плейлиста се актуализира на всеки час и ако случайно картината ви забие, спре или не се зарежда, моля презаредете плейлиста от вашия IPTV плеър!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Назад</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <img id="zoomedImage" src="" class="img-zoomed">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Затвори</button>
                </div>
            </div>
        </div>
    </div>

<!-- Footer -->
<footer class="footer mt-auto">
    <div class="container">
        <div class="footer-content">
            <div class="user-stats">
                <div class="stat-item">
                    <span class="stat-icon"><i class="fas fa-users"></i></span>
                    <span>Регистрирани: </span>
                    <span class="stat-value"><?php echo $totalUsers; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-icon"><i class="fas fa-eye"></i></span>
                    <span>Активни: </span>
                    <span class="stat-value"><?php echo $activeViewers; ?></span>
                </div>
            </div>
            <div class="footer-bulgaria">България над всичко!</div>
            <div class="footer-rights">Nyama Fun &copy; <?php echo date('Y'); ?> Всички права запазени</div>
            <div class="mt-2">
                <a href="faq.php" class="terms-link">Общи условия</a>
            </div>
        </div>
    </div>
</footer>

    <div id="copiedAlert" class="copied-alert alert alert-success d-none">
        <i class="fas fa-check-circle me-2"></i>
        <span class="alert-text">Копиран!</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function copyCredentials(button) {
        const text = button.getAttribute('data-value');
        const icon = button.querySelector('i');
        const alertBox = document.getElementById('copiedAlert');
        const alertText = alertBox.querySelector('.alert-text');

        const legacyCopy = text => {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                return successful;
            } catch (err) {
                document.body.removeChild(textArea);
                return false;
            }
        };

        const modernCopy = async text => {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                return false;
            }
        };

        const handleCopy = async () => {
            let success = false;
            
            if (navigator.clipboard) {
                success = await modernCopy(text);
            } else {
                success = legacyCopy(text);
            }

            if (success) {
                alertBox.classList.remove('alert-danger');
                alertBox.classList.add('alert-success');
                alertText.textContent = 'URL-a е копиран !';
                
                button.classList.add('btn-success');
                button.classList.remove('btn-outline-primary');
            } else {
                alertBox.classList.remove('alert-success');
                alertBox.classList.add('alert-danger');
                alertText.textContent = 'Copy failed! Select text and press Ctrl+C';
            }

            alertBox.classList.remove('d-none');
            setTimeout(() => {
                alertBox.classList.add('d-none');
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-primary');
            }, 2500);
        };

        handleCopy().catch(err => {
            console.error('Copy error:', err);
            alertText.textContent = 'Copy failed! Please manually copy';
            alertBox.classList.remove('d-none');
            alertBox.classList.add('alert-danger');
        });
    }
    
    function setZoomedImage(src) {
        document.getElementById('zoomedImage').src = src;
    }
    </script>
</body>
</html>
