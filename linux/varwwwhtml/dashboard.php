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

// Device usage check
$deviceUsageFile = 'user_device_usage.json';
$deviceInfo = null;

if (file_exists($deviceUsageFile)) {
    $deviceUsage = json_decode(file_get_contents($deviceUsageFile), true);
    if (is_array($deviceUsage) && isset($deviceUsage[$data['xtream_username']])) {
        $deviceInfo = $deviceUsage[$data['xtream_username']];
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
        $_SESSION['error'] = 'Incorrect password!';
        header("Location: dashboard.php");
        exit();
    }

    // Remove device entry
    if (file_exists($deviceUsageFile)) {
        $deviceUsage = json_decode(file_get_contents($deviceUsageFile), true);
        if (is_array($deviceUsage) && isset($deviceUsage[$data['xtream_username']])) {
            unset($deviceUsage[$data['xtream_username']]);
            file_put_contents($deviceUsageFile, json_encode($deviceUsage));
        }
    }

    $newUsername = $data['username'];
    $newPassword = bin2hex(random_bytes(16));

    $updateStmt = $pdo->prepare("UPDATE xtream_codes SET xtream_username = ?, xtream_password = ? WHERE user_id = ?");
    $updateStmt->execute([$newUsername, $newPassword, $_SESSION['user_id']]);

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
                        <h4 class="mb-0">Welcome, <?= htmlspecialchars($data['username']) ?>!</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5 class="alert-heading">Account Details</h5>
                            <div class="text-muted">Enjoy! Use your URL's with your favorite IPTV Player. Only 1 device per account!</div>
                            <hr>

                            <!-- Updated username/password section -->
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Username:</strong>
                                            <div class="font-monospace"><?= htmlspecialchars($data['xtream_username']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Password:</strong>
                                            <div class="font-monospace"><?= htmlspecialchars($data['xtream_password']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Updated URL sections -->
                            <div class="url-section">
                                <h6 class="mb-2"><i class="fas fa-list me-2"></i>M3U Playlist URL</h6>
                                
                                <div class="mb-3">
                                    <div class="url-title"><i class="fas fa-link fa-sm"></i> Original URL</div>
                                    <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-center url-flex-container">
                                        <div class="font-monospace me-md-2 mb-md-0 mb-2">
                                            <?= htmlspecialchars($originalUrl) ?>
                                        </div>
                                        <button class="btn copy-btn btn-outline-primary ms-md-2" 
                                                data-value="<?= htmlspecialchars($originalUrl) ?>"
                                                onclick="copyCredentials(this)">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="url-title"><i class="fas fa-compress-alt fa-sm"></i> Short URL </div>
                                    <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-center url-flex-container">
                                        <div class="font-monospace me-md-2 mb-md-0 mb-2">
                                            <?= htmlspecialchars($shortUrl) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="url-section">
                                <h6 class="mb-2"><i class="fas fa-tv me-2"></i>EPG Guide URL</h6>
                                <div class="d-flex flex-md-row flex-column justify-content-between align-items-md-center url-flex-container">
                                    <div class="font-monospace me-md-2 mb-md-0 mb-2">
                                        http://epg.cloudns.org/dl.php
                                    </div>
                                    <button class="btn copy-btn btn-outline-primary ms-md-2" 
                                            data-value="http://epg.cloudns.org/dl.php"
                                            onclick="copyCredentials(this)">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>


                        <?php if ($deviceInfo): ?>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Account connected to <?= htmlspecialchars($deviceInfo['user_agent']) ?>, Since <?= date('d-m-Y H:i:s', $deviceInfo['timestamp']) ?><br>
                                <small class="text-muted">
                                    Device ID: <?= htmlspecialchars($deviceInfo['device_id']) ?> |
                                    IP: <?= htmlspecialchars($deviceInfo['ip']) ?>
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mb-3">
                                <i class="fas fa-check-circle me-2"></i>
                                No active connections
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($deviceInfo): ?>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                    <i class="fas fa-sync-alt me-2"></i>Disconnect from Device
                                </button>
                            <?php else: ?>
                                <div class="text-muted">You can now connect to a device</div>
                            <?php endif; ?>
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Confirmation Modal -->
        <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="passwordModalLabel">Confirm Account Password</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p>For security reasons, please enter your account password to disconnect the device:</p>
                            <input type="hidden" name="action" value="change_device">
                            <div class="mb-3">
                                <label for="passwordInput" class="form-label">Account Password</label>
                                <input type="password" class="form-control bg-dark text-light border-secondary" 
                                       id="passwordInput" name="password" required>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Confirm Disconnect</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="copiedAlert" class="copied-alert alert alert-success d-none">
        <i class="fas fa-check-circle me-2"></i>
        <span class="alert-text">Copied to clipboard!</span>
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
                alertText.textContent = 'Copied to clipboard!';
                
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
