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

    // Remove any existing session for this user
    if (file_exists($sessions_file)) {
        $sessions = json_decode(file_get_contents($sessions_file), true);
        $accountKey = md5($data['xtream_username'] . ':' . $data['xtream_password']);
        
        if (isset($sessions[$accountKey])) {
            unset($sessions[$accountKey]);
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
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0">Здравей, <?= htmlspecialchars($data['username']) ?>!
                            <span class="watching-status <?= $isWatching ? 'watching-now' : 'not-watching' ?>">
                                <span class="status-dot <?= $isWatching ? 'watching-dot' : 'not-watching-dot' ?>"></span>
                                <?= $isWatching ? 'Заключен към устройство' : 'Не е заключен' ?>
                            </span>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($isWatching && $watchingInfo): ?>
                        <div class="watching-info">
                            <i class="fas fa-tv me-1"></i>
                            <strong>Активна сесия:</strong><br>
                            Устройство: <?= htmlspecialchars($watchingInfo['ua']) ?><br>
                            IP адрес: <?= htmlspecialchars($watchingInfo['ip']) ?><br>
                            Последна активност: <?= date('Y-m-d H:i:s', $watchingInfo['last_seen']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info mt-3">
                            <h5 class="alert-heading">Детайли за акаунта:</h5>
                            <div class="text-muted">Използвай линковете в любимия ти IPTV плеър и се наслаждавай на безплатна българска телевизия.
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
    </script>
</body>
</html>
