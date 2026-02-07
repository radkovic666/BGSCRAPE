<?php
session_start();
ob_start();

// Handle logout first
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Check if user is already logged in
if (!isset($_SESSION['admin_logged_in'])) {
    // Display login form if not logged in
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        // Include config to get credentials
        require 'config.php';
        
        // Check credentials
        $input_username = $_POST['username'];
        $input_password = $_POST['password'];
        
        if ($input_username === $username && $input_password === $password) {
            $_SESSION['admin_logged_in'] = true;
            // Redirect to avoid form resubmission
            header("Location: index.php");
            exit();
        } else {
            $loginError = "Invalid credentials!";
        }
    }
    
    // Show login form if not logged in
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Please</title>
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
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            body {
                background-color: #000000; /* Changed to black */
                color: var(--dark-text);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .login-container {
                background-color: var(--dark-bg);
                border: 1px solid var(--dark-border);
                border-radius: 15px;
                padding: 40px;
                width: 100%;
                max-width: 400px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            }
            
            h1 {
                color: var(--accent-blue);
                text-align: center;
                margin-bottom: 30px;
                font-size: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }
            
            .form-group {
                margin-bottom: 25px;
            }
            
            label {
                display: block;
                margin-bottom: 8px;
                color: var(--github-gray);
                font-weight: 500;
            }
            
            input[type="text"],
            input[type="password"] {
                width: 100%;
                padding: 12px 15px;
                background-color: var(--darker-bg);
                border: 1px solid var(--dark-border);
                border-radius: 8px;
                color: var(--dark-text);
                font-size: 16px;
                transition: all 0.3s ease;
            }
            
            input[type="text"]:focus,
            input[type="password"]:focus {
                outline: none;
                border-color: var(--accent-blue);
                box-shadow: 0 0 0 3px rgba(47, 129, 247, 0.2);
            }
            
            .btn {
                width: 100%;
                padding: 14px;
                background-color: var(--accent-blue);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }
            
            .btn:hover {
                background-color: #1c6ed8;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(47, 129, 247, 0.3);
            }
            
            .error {
                color: #f72585;
                text-align: center;
                margin-top: 15px;
                padding: 10px;
                background-color: rgba(247, 37, 133, 0.1);
                border-radius: 5px;
                border: 1px solid rgba(247, 37, 133, 0.2);
            }
            
            .logo {
                text-align: center;
                margin-bottom: 30px;
                color: white;
                font-size: 24px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1><i class="fas fa-lock"></i>Login</h1>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-key"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <?php if (isset($loginError)): ?>
                    <div class="error"><?php echo $loginError; ?></div>
                <?php endif; ?>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If we reach this point, the user is logged in
require 'config.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Reset device binding
    if (isset($_POST['reset_user_id'])) {
        $userId = $_POST['reset_user_id'];
        
        // Find the user in our array
        $stmt = $pdo->prepare("SELECT * FROM device_bindings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $binding = $stmt->fetch();
        
        if ($binding) {
            // Remove from device_bindings table
            $deleteStmt = $pdo->prepare("DELETE FROM device_bindings WHERE user_id = ?");
            $deleteStmt->execute([$userId]);
            
            // Log the reset action
            $logStmt = $pdo->prepare("INSERT INTO device_binding_logs (user_id, action, fingerprint, user_agent, ip_address) 
                                     VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([
                $userId,
                'admin_reset',
                $binding['device_fingerprint'],
                $binding['user_agent'],
                $binding['ip_address']
            ]);
            
            $_SESSION['success'] = "Устройство е отключено на абонат с ID: $userId";
        } else {
            $_SESSION['error'] = "Грешка в отключване на устройство на абонат с ID: $userId";
        }
    }
    
    // 2. Add new user
    elseif (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        
        // Validation
        $errors = [];
        if (empty($username)) $errors[] = "Username is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (!preg_match('/^[a-z0-9_]+$/i', $username)) $errors[] = "Username can only contain letters, numbers and underscore";
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists";
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Generate random password
                $password = bin2hex(random_bytes(8)); // 16 character password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ip = '127.0.0.1'; // Admin created user
                
                // Create user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, ip_address, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashed, $ip]);
                $user_id = $pdo->lastInsertId();
                
                // Generate Xtream code
                $xt_user = $username;
                $xt_pass = bin2hex(random_bytes(16)); // Secure random password
                
                $stmt = $pdo->prepare("INSERT INTO xtream_codes (user_id, xtream_username, xtream_password) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $xt_user, $xt_pass]);
                
                $pdo->commit();
                
                $_SESSION['success'] = "Абонатът е добавен успешно!";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = $e->getCode() == 23000 ? "User already exists!" : "Error creating user: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    
    // 3. Delete user
    elseif (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Delete user and all related data (foreign keys should handle cascade)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            $_SESSION['success'] = "Абонатът е премахнат успешно!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Грешка при премахване на абонат " . $e->getMessage();
        }
    }
    
    // 4. Update user
    elseif (isset($_POST['update_user'])) {
        $userId = $_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $status = $_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, status = ? WHERE id = ?");
            $stmt->execute([$username, $email, $status, $userId]);
            $_SESSION['success'] = "User updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating user: " . $e->getMessage();
        }
    }
    
    // 5. Regenerate Xtream credentials
    elseif (isset($_POST['regenerate_credentials'])) {
        $userId = $_POST['user_id'];
        
        try {
            // Generate new Xtream password
            $newPassword = bin2hex(random_bytes(16));
            
            $stmt = $pdo->prepare("UPDATE xtream_codes SET xtream_password = ? WHERE user_id = ?");
            $stmt->execute([$newPassword, $userId]);
            
            $_SESSION['success'] = "Xtream credentials regenerated for user ID: $userId";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error regenerating credentials: " . $e->getMessage();
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: index.php");
    exit();
}

// Get all users from database with their device bindings and Xtream codes
$stmt = $pdo->query("
    SELECT 
        u.id, 
        u.username, 
        u.email,
        u.password,
        u.status as user_status,
        u.created_at,
        u.ip_address as registration_ip,
        x.xtream_username,
        x.xtream_password,
        x.is_active,
        db.id as binding_id,
        db.account_key,
        db.device_fingerprint,
        db.user_agent,
        db.ip_address as device_ip,
        db.last_seen,
        db.created_at as binding_created
    FROM users u
    LEFT JOIN xtream_codes x ON u.id = x.user_id
    LEFT JOIN device_bindings db ON u.id = db.user_id
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data for display
$allUsers = [];

foreach ($users as $user) {
    $userId = $user['id'];
    
    if (!isset($allUsers[$userId])) {
        $allUsers[$userId] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password'],
            'user_status' => $user['user_status'],
            'created_at' => $user['created_at'],
            'registration_ip' => $user['registration_ip'],
            'xtream_username' => $user['xtream_username'],
            'xtream_password' => $user['xtream_password'],
            'is_active' => $user['is_active'],
            'devices' => []
        ];
    }
    
    // Add device if exists
    if ($user['binding_id']) {
        $allUsers[$userId]['devices'][] = [
            'binding_id' => $user['binding_id'],
            'device_fingerprint' => $user['device_fingerprint'],
            'user_agent' => $user['user_agent'],
            'device_ip' => $user['device_ip'],
            'last_seen' => $user['last_seen'],
            'binding_created' => $user['binding_created']
        ];
    }
}

// Prepare statistics
$totalUsers = count($allUsers);
$boundDevices = 0;
$notBound = 0;
$activeDevices = 0;

foreach ($allUsers as $user) {
    if (!empty($user['devices'])) {
        $boundDevices++;
        // Check if any device is active (within last 5 minutes)
        foreach ($user['devices'] as $device) {
            if ($device['last_seen']) {
                $lastSeen = strtotime($device['last_seen']);
                $now = time();
                if (($now - $lastSeen) <= 300) {
                    $activeDevices++;
                    break;
                }
            }
        }
    } else {
        $notBound++;
    }
}

// Get registration attempts in last 24 hours
$stmt = $pdo->query("SELECT COUNT(*) as attempts FROM registration_attempts WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$recentAttempts = $stmt->fetch()['attempts'];

// Display messages if any
$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nyama.Fun Админ Панел</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #0d1117;
            --darker-bg: #010409;
            --dark-border: #30363d;
            --dark-text: #e6edf3;
            --accent-blue: #2f81f7;
            --github-gray: #8b949e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* Specific hover for the report button */
a.btn-custom[href="scrapelog.html"]:hover {
    background-color: #248c3a !important;
}
        body {
            background-color: var(--darker-bg);
            color: var(--dark-text);
            min-height: 100vh;
        }
        
        .container-fluid {
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--dark-bg);
            border-radius: 10px;
            border: 1px solid var(--dark-border);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .ascii-art {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 12px;
            color: var(--accent-white);
            text-align: center;
            white-space: pre;
            margin: 0;
        }
        
        .ascii-art-small {
            font-family: 'Courier New', monospace;
            font-size: 5px;
            line-height: 5px;
            color: var(--accent-blue);
            text-align: center;
            white-space: pre;
            margin: 0;
        }
        
        .ascii-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .ascii-title {
            color: #fafafa
            font-weight: bold;
            font-size: 14px;
            margin-top: 5px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--dark-bg);
            border: 1px solid var(--dark-border);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: var(--github-gray);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-card .icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .stat-total-users { color: var(--accent-blue); }
        .stat-bound-devices { color: #4cc9f0; }
        .stat-not-bound { color: #f8961e; }
        .stat-active-devices { color: #2ea043; }
        .stat-reg-attempts { color: #f72585; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--dark-bg);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--dark-border);
        }
        
        thead {
            background-color: var(--darker-bg);
            border-bottom: 2px solid var(--dark-border);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--dark-border);
            color: var(--dark-text);
        }
        
        th {
            font-weight: 600;
            color: var(--accent-blue);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        tbody tr:hover {
            background-color: rgba(47, 129, 247, 0.1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-bound {
            background-color: rgba(47, 129, 247, 0.2);
            color: #2f81f7;
            border: 1px solid rgba(47, 129, 247, 0.3);
        }
        
        .status-not-bound {
            background-color: rgba(247, 37, 133, 0.2);
            color: #f72585;
            border: 1px solid rgba(247, 37, 133, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view {
            background-color: var(--accent-blue);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #1c6ed8;
        }
        
        .btn-reset {
            background-color: #f8961e;
            color: white;
        }
        
        .btn-reset:hover {
            background-color: #e4850c;
        }
        
        .btn-delete {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #d3126f;
        }
        
        .btn-edit {
            background-color: #4cc9f0;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #2fb5e0;
        }
        
        .btn-regenerate {
            background-color: #9d4edd;
            color: white;
        }
        
        .btn-regenerate:hover {
            background-color: #7b2cbf;
        }
        
        .modal-content {
            background-color: var(--dark-bg);
            color: var(--dark-text);
            border: 1px solid var(--dark-border);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--dark-border);
        }
        
        .modal-title {
            color: var(--accent-blue);
        }
        
        .modal-footer {
            border-top: 1px solid var(--dark-border);
        }
        
        .form-label {
            color: var(--github-gray);
        }
        
        .form-control, .form-select {
            background-color: var(--darker-bg);
            border: 1px solid var(--dark-border);
            color: var(--dark-text);
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--darker-bg);
            border-color: var(--accent-blue);
            color: var(--dark-text);
            box-shadow: 0 0 0 0.25rem rgba(47, 129, 247, 0.25);
        }
        
        .url-box {
            background-color: var(--darker-bg);
            border: 1px solid var(--dark-border);
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
        
        .copy-btn {
            background-color: var(--accent-blue);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .copy-btn:hover {
            background-color: #1c6ed8;
        }
        
        .alert {
            border: 1px solid transparent;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            color: #2ea043;
            background-color: rgba(46, 160, 67, 0.1);
            border-color: rgba(46, 160, 67, 0.2);
        }
        
        .alert-error {
            color: #f72585;
            background-color: rgba(247, 37, 133, 0.1);
            border-color: rgba(247, 37, 133, 0.2);
        }
        
        .device-info {
            background-color: var(--darker-bg);
            border: 1px solid var(--dark-border);
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--accent-blue);
        }
        
        .last-updated {
            color: var(--github-gray);
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--dark-border);
        }
        
        .btn-group-header {
            display: flex;
            gap: 10px;
        }
        
        .btn-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-add-user {
            background-color: var(--accent-blue);
            color: white;
            border: none;
        }
        
        .btn-add-user:hover {
            background-color: #1c6ed8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(47, 129, 247, 0.3);
        }
        
        .btn-refresh {
            background-color: var(--dark-bg);
            color: var(--dark-text);
            border: 1px solid var(--dark-border);
        }
        
        .btn-refresh:hover {
            background-color: var(--darker-bg);
            border-color: var(--accent-blue);
        }
        
        .btn-logout {
            background-color: #f72585;
            color: white;
            border: none;
        }
        
        .btn-logout:hover {
            background-color: #d3126f;
        }
        
        .view-user-content {
            padding: 20px;
        }
        
        .user-greeting {
            font-size: 24px;
            margin-bottom: 10px;
            color: white;
        }
        
        .user-password {
            font-size: 18px;
            margin-bottom: 15px;
            color: #4cc9f0;
            background-color: rgba(76, 201, 240, 0.1);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(76, 201, 240, 0.3);
        }
        
        .user-device-status {
            font-size: 16px;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        
        .user-not-bound {
            background-color: rgba(5, 251, 0, 0.1);
            color: #05fb00;
            border: 1px solid rgba(100, 251, 0, 0.3);
        }
        
        .user-bound {
            background-color: rgba(255, 210, 69, 0.1);
            color: #ffd245;
            border: 1px solid rgba(155, 210, 69, 0.3);
        }
        
        .account-details {
            margin-top: 20px;
        }
        
        .url-section {
            margin: 15px 0;
            padding: 15px;
            background-color: rgba(47, 129, 247, 0.05);
            border-radius: 5px;
            border-left: 3px solid var(--accent-blue);
        }
        
        .url-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: white;
        }
        
        .url-value {
            font-family: monospace;
            color: #4cc9f0;
            word-break: break-all;
            padding: 8px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        @media (max-width: 768px) {
            .container-fluid {
                padding: 10px;
            }
            
            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .btn-group-header {
                flex-wrap: wrap;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .ascii-art {
                font-size: 4px;
                line-height: 4px;
            }
            
            .ascii-art-small {
                font-size: 3px;
                line-height: 3px;
            }
        }
        
        @media (max-width: 480px) {
            .ascii-art {
                font-size: 3px;
                line-height: 3px;
            }
            
            .ascii-art-small {
                font-size: 2px;
                line-height: 2px;
            }
            
            .ascii-title {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
<header>
    <div class="ascii-container">
        <div class="ascii-art">    )      )            *              (              ) 
 ( /(   ( /(   (      (  `     (       )\ )        ( /( 
 )\())  )\())  )\     )\))(    )\     (()/(    (   )\())
((_)\  ((_)\((((_)(  ((_)()\((((_)(    /(_))   )\ ((_)\ 
 _((_)__ ((_))\ _ )\ (_()((_))\ _ )\  (_))_|_ ((_) _((_)
| \| |\ \ / /(_)_\(_)|  \/  |(_)_\(_) | |_ | | | || \| |
| .` | \ V /  / _ \  | |\/| | / _ \   | __|| |_| || .` |
|_|\_|  |_|  /_/ \_\ |_|  |_|/_/ \_\  |_|   \___/ |_|\_|</div>
        <div class="ascii-title">Няма пълно щастие !</div>
    </div>
    <div class="btn-group-header">
		        <button class="btn-custom btn-add-user" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus"></i> Добави Абонат
        </button>
        <a href="scrapelog.html" class="btn-custom" style="background-color: #2ea043; color: white; border: none;">
            <i class="fas fa-file-alt"></i> Доклад
        </a>
        <button class="btn-custom btn-refresh" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Опресни
        </button>
        <a href="index.php?logout=1" class="btn-custom btn-logout">
            <i class="fas fa-sign-out-alt"></i> Излез
        </a>
    </div>
</header>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon stat-total-users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="value stat-total-users"><?php echo $totalUsers; ?></div>
                <h3>Общо Абонати</h3>
            </div>
            <div class="stat-card">
                <div class="icon stat-bound-devices">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="value stat-bound-devices"><?php echo $boundDevices; ?></div>
                <h3>Заключени към устройство</h3>
            </div>
            <div class="stat-card">
                <div class="icon stat-not-bound">
                    <i class="fas fa-unlink"></i>
                </div>
                <div class="value stat-not-bound"><?php echo $notBound; ?></div>
                <h3>Незаключени</h3>
            </div>
            <div class="stat-card">
                <div class="icon stat-active-devices">
                    <i class="fas fa-wifi"></i>
                </div>
                <div class="value stat-active-devices"><?php echo $activeDevices; ?></div>
                <h3>Гледат в момента</h3>
            </div>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Име</th>
                        <th>Email</th>
                        <th>Добавен на</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $user): 
                        // Generate URLs
                        $m3uUrl = 'http://nyama.fun/playlist.m3u?username=' . urlencode($user['xtream_username']) . '&password=' . urlencode($user['xtream_password']);
                        $shortUrl = @file_get_contents('https://is.gd/create.php?format=simple&url=' . urlencode($m3uUrl));
                        if (!$shortUrl) $shortUrl = $m3uUrl;
                        $epgUrl = 'https://is.gd/tvbgepg';
                        $isBound = !empty($user['devices']);
                        $deviceInfo = $isBound ? $user['devices'][0] : null;
                    ?>
                    <tr data-user-id="<?php echo $user['id']; ?>"
                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                        data-status="<?php echo $user['user_status']; ?>"
                        data-xtream-username="<?php echo htmlspecialchars($user['xtream_username']); ?>"
                        data-xtream-password="<?php echo htmlspecialchars($user['xtream_password']); ?>"
                        data-password="<?php echo htmlspecialchars($user['password']); ?>"
                        data-m3u-url="<?php echo htmlspecialchars($shortUrl); ?>"
                        data-epg-url="<?php echo htmlspecialchars($epgUrl); ?>"
                        data-is-bound="<?php echo $isBound ? 'true' : 'false'; ?>"
                        data-user-agent="<?php echo $isBound ? htmlspecialchars($deviceInfo['user_agent']) : ''; ?>"
                        data-device-ip="<?php echo $isBound ? htmlspecialchars($deviceInfo['device_ip']) : ''; ?>"
                        data-last-seen="<?php echo $isBound ? htmlspecialchars($deviceInfo['last_seen']) : ''; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>

                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($isBound): ?>
                                <span class="status-badge status-bound">
                                    <i class="fas fa-link me-1"></i> Заключен
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-not-bound">
                                    <i class="fas fa-unlink me-1"></i> Незаключен
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-view" onclick="viewUserDetails(this)">
                                    <i class="fas fa-eye"></i> Прегледай
                                </button>
                                <button class="btn-action btn-edit" onclick="editUser(this)">
                                    <i class="fas fa-edit"></i> Редактирай
                                </button>
                                <button class="btn-action btn-regenerate" onclick="regenerateCredentials(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-key"></i> Регенерирай
                                </button>
                                <?php if ($isBound): ?>
                                <form method="POST" action="index.php" style="display: inline;">
                                    <input type="hidden" name="reset_user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn-action btn-reset" 
                                            onclick="return confirm('Отключи устройството на <?php echo htmlspecialchars($user['username']); ?>?')">
                                        <i class="fas fa-unlink"></i> Отключи
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="index.php" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="delete_user" value="1">
                                    <button type="submit" class="btn-action btn-delete" 
                                            onclick="return confirm('Delete user <?php echo htmlspecialchars($user['username']); ?>? This action cannot be undone!')">
                                        <i class="fas fa-trash"></i> Изтрий
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="last-updated" id="last-updated">Last updated: <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Нов Абонат</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="index.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Потребителско име *</label>
                            <input type="text" class="form-control" name="username" required 
                                   pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="20"
                                   placeholder="Само букви, числа и долна черта">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Адрес *</label>
                            <input type="email" class="form-control" name="email" required
                                   placeholder="user@example.com">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Назад</button>
                        <button type="submit" name="add_user" value="1" class="btn btn-primary">Добави абонат</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>Детайли за абонат</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewUserModalBody">
                    <!-- Content will be loaded via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Назад</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Редактиране на абонат</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="index.php">
                    <div class="modal-body" id="editUserModalBody">
                        <!-- Content will be loaded via JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Назад</button>
                        <button type="submit" name="update_user" value="1" class="btn btn-primary">Запази</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Regenerate Credentials Modal -->
    <div class="modal fade" id="regenerateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Регенерирай Xtream детайли</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="index.php">
                    <div class="modal-body">
                        <p>Сигурен ли си че искаш да промениш Xtream акаунта на този абонат?</p>
                        <p class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Това ще инвалидира сегашния M3U URL!</p>
                        <input type="hidden" name="user_id" id="regenerateUserId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Назад</button>
                        <button type="submit" name="regenerate_credentials" value="1" class="btn btn-warning">Регенерирай</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View user details
        function viewUserDetails(button) {
            const row = button.closest('tr');
            const userId = row.getAttribute('data-user-id');
            const username = row.getAttribute('data-username');
            const email = row.getAttribute('data-email');
            const status = row.getAttribute('data-status');
            const xtreamUsername = row.getAttribute('data-xtream-username');
            const xtreamPassword = row.getAttribute('data-xtream-password');
            const password = row.getAttribute('data-password');
            const m3uUrl = row.getAttribute('data-m3u-url');
            const epgUrl = row.getAttribute('data-epg-url');
            const isBound = row.getAttribute('data-is-bound') === 'true';
            const userAgent = row.getAttribute('data-user-agent');
            const deviceIp = row.getAttribute('data-device-ip');
            const lastSeen = row.getAttribute('data-last-seen');
            
            const modalBody = document.getElementById('viewUserModalBody');
            
            // Format last seen time if available
            let lastSeenFormatted = 'N/A';
            if (lastSeen) {
                const lastSeenDate = new Date(lastSeen);
                lastSeenFormatted = lastSeenDate.toLocaleString();
            }
            
            modalBody.innerHTML = `
                <div class="view-user-content">
                    <div class="user-greeting">Данни за абонат ${username}</div>
                    <div class="user-device-status ${isBound ? 'user-bound' : 'user-not-bound'}">
                        ${isBound ? 'Заключен към устройство' : 'Не е заключен към устройство'}
                    </div>
                    
                    <div class="account-details">
                        <h5>Детайли за акаунта:</h5>
                        
                        <div class="url-section">
                            <div class="url-title">Плейлист M3U URL</div>
                            <div class="url-value">${m3uUrl}</div>
                            <button class="copy-btn mt-2" onclick="copyToClipboard('${m3uUrl}')">
                                <i class="fas fa-copy"></i> Copy URL
                            </button>
                        </div>
                        
                        <div class="url-section">
                            <div class="url-title">EPG програма URL</div>
                            <div class="url-value">${epgUrl}</div>
                            <button class="copy-btn mt-2" onclick="copyToClipboard('${epgUrl}')">
                                <i class="fas fa-copy"></i> Copy URL
                            </button>
                        </div>
                                             
                        ${isBound ? `
                        <div class="mt-4">
                            <h6>Информация за устройство:</h6>
                            <table class="table table-sm table-borderless">
                                <tr><td><strong>Xtream Username:</strong></td><td>${xtreamUsername}</td></tr>
                                <tr><td><strong>Xtream Password:</strong></td><td>${xtreamPassword}</td></tr>
                                <tr><td><strong>User Agent:</strong></td><td>${userAgent || 'N/A'}</td></tr>
                                <tr><td><strong>IP Address:</strong></td><td>${deviceIp || 'N/A'}</td></tr>
                                <tr><td><strong>Last Seen:</strong></td><td>${lastSeenFormatted}</td></tr>
								<tr><td><strong>Status:</strong></td><td>${status}</td></tr>
                            </table>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
            modal.show();
        }
        
        // Edit user
        function editUser(button) {
            const row = button.closest('tr');
            const userId = row.getAttribute('data-user-id');
            const username = row.getAttribute('data-username');
            const email = row.getAttribute('data-email');
            const status = row.getAttribute('data-status');
            
            const modalBody = document.getElementById('editUserModalBody');
            
            modalBody.innerHTML = `
                <input type="hidden" name="user_id" value="${userId}">
                <div class="mb-3">
                    <label class="form-label">Потребителско Име</label>
                    <input type="text" class="form-control" name="username" value="${username}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Aдрес</label>
                    <input type="email" class="form-control" name="email" value="${email}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Промени Статус</label>
                    <select class="form-select" name="status">
                        <option value="active" ${status === 'active' ? 'selected' : ''}>Активиран</option>
                        <option value="suspended">Блокиран</option>
                    </select>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
        
        // Regenerate credentials
        function regenerateCredentials(userId) {
            document.getElementById('regenerateUserId').value = userId;
            const modal = new bootstrap.Modal(document.getElementById('regenerateModal'));
            modal.show();
        }
        
        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }
        
        // Auto-refresh every 60 seconds
        setInterval(() => {
            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }, 60000);
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
