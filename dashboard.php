<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT users.username, xtream_codes.* 
                      FROM users 
                      JOIN xtream_codes ON users.id = xtream_codes.user_id 
                      WHERE users.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$data = $stmt->fetch();

// Device usage check
$deviceUsageFile = 'user_device_usage.json';
$isConnected = false;

if (file_exists($deviceUsageFile)) {
    $deviceUsage = json_decode(file_get_contents($deviceUsageFile), true);
    if (is_array($deviceUsage) && array_key_exists($data['xtream_username'], $deviceUsage)) {
        $isConnected = true;
    }
}

// Handle Change Device request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_device') {
    // Remove device entry
    if (file_exists($deviceUsageFile)) {
        $deviceUsage = json_decode(file_get_contents($deviceUsageFile), true);
        if (is_array($deviceUsage) && isset($deviceUsage[$data['xtream_username']])) {
            unset($deviceUsage[$data['xtream_username']]);
            file_put_contents($deviceUsageFile, json_encode($deviceUsage));
        }
    }

    // Generate new credentials
    $newUsername = $_SESSION['user_id'] . '-' . bin2hex(random_bytes(4));
    $newPassword = bin2hex(random_bytes(8));

    // Update database
    $updateStmt = $pdo->prepare("UPDATE xtream_codes SET xtream_username = ?, xtream_password = ? WHERE user_id = ?");
    $updateStmt->execute([$newUsername, $newPassword, $_SESSION['user_id']]);

    // Redirect to prevent resubmission
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
        }
        @keyframes slideIn {
            from { right: -300px; }
            to { right: 20px; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .font-monospace {
            user-select: all;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Welcome, <?= htmlspecialchars($data['username']) ?>!</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5 class="alert-heading">Your Xtream Codes</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Username:</strong>
                                            <div class="font-monospace"><?= htmlspecialchars($data['xtream_username']) ?></div>
                                        </div>
                                        <button class="btn copy-btn btn-outline-primary" 
                                                data-value="<?= htmlspecialchars($data['xtream_username']) ?>"
                                                onclick="copyCredentials(this)">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Password:</strong>
                                            <div class="font-monospace"><?= htmlspecialchars($data['xtream_password']) ?></div>
                                        </div>
                                        <button class="btn copy-btn btn-outline-primary" 
                                                data-value="<?= htmlspecialchars($data['xtream_password']) ?>"
                                                onclick="copyCredentials(this)">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($isConnected): ?>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                You are currently connected to a device.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mb-3">
                                <i class="fas fa-check-circle me-2"></i>
                                You are not connected to any device.
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($isConnected): ?>
                                <form method="post" onsubmit="return confirm('This will generate new credentials and disconnect your current device! Continue?')">
                                    <input type="hidden" name="action" value="change_device">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-sync-alt me-2"></i>Change Device
                                    </button>
                                </form>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            <a href="logout.php" class="btn btn-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="copiedAlert" class="copied-alert alert alert-success d-none">
        <i class="fas fa-check-circle me-2"></i>
        <span class="alert-text">Copied to clipboard!</span>
    </div>

    <script>
    function copyCredentials(button) {
        const text = button.getAttribute('data-value');
        const icon = button.querySelector('i');
        const alertBox = document.getElementById('copiedAlert');
        const alertText = alertBox.querySelector('.alert-text');

        // Fallback method for older browsers
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

        // Modern clipboard API
        const modernCopy = async text => {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                return false;
            }
        };

        // Main copy handler
        const handleCopy = async () => {
            let success = false;
            
            if (navigator.clipboard) {
                success = await modernCopy(text);
            } else {
                success = legacyCopy(text);
            }

            // Visual feedback
            if (success) {
                alertBox.classList.remove('alert-danger');
                alertBox.classList.add('alert-success');
                alertText.textContent = 'Copied to clipboard!';
                
                icon.classList.replace('fa-copy', 'fa-copy');
                button.classList.add('btn-success');
                button.classList.remove('btn-outline-primary');
            } else {
                alertBox.classList.remove('alert-success');
                alertBox.classList.add('alert-danger');
                alertText.textContent = 'Copy failed! Select text and press Ctrl+C';
            }

            // Show alert
            alertBox.classList.remove('d-none');
            setTimeout(() => {
                alertBox.classList.add('d-none');
                
                // Reset button state
                if (success) {
                    setTimeout(() => {
                        icon.classList.replace('fa-copy', 'fa-copy');
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-primary');
                    }, 100);
                }
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