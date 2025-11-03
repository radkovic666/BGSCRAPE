<?php
session_start();

// Handle logout first
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: dd.php");
    exit();
}

// Check if user is already logged in
if (!isset($_SESSION['admin_logged_in'])) {
    // Display login form if not logged in
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        // Include config to get credentials
        require 'config.php';
        
        // Check credentials - FIXED: Added proper validation
        $input_username = $_POST['username'];
        $input_password = $_POST['password'];
        
        if ($input_username === $username && $input_password === $password) {
            $_SESSION['admin_logged_in'] = true;
            // Redirect to avoid form resubmission
            header("Location: dd.php");
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
        <title>Admin Login</title>
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
                background-color: var(--darker-bg);
                color: var(--dark-text);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .login-container {
                background-color: var(--dark-bg);
                border: 1px solid var(--dark-border);
                border-radius: 10px;
                padding: 30px;
                width: 100%;
                max-width: 400px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            
            h1 {
                color: var(--accent-blue);
                text-align: center;
                margin-bottom: 20px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            label {
                display: block;
                margin-bottom: 8px;
                color: var(--github-gray);
            }
            
            input[type="text"],
            input[type="password"] {
                width: 100%;
                padding: 10px;
                background-color: var(--darker-bg);
                border: 1px solid var(--dark-border);
                border-radius: 5px;
                color: var(--dark-text);
                font-size: 16px;
            }
            
            input[type="text"]:focus,
            input[type="password"]:focus {
                outline: none;
                border-color: var(--accent-blue);
            }
            
            .btn {
                width: 100%;
                padding: 12px;
                background-color: var(--accent-blue);
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .btn:hover {
                background-color: #1c6ed8;
            }
            
            .error {
                color: #f72585;
                text-align: center;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1><i class="fas fa-lock"></i> Admin Login</h1>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
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
// Continue with the dashboard code

require 'config.php';

// Get all users from database
$stmt = $pdo->query("SELECT id, username FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load sessions data
$sessionsFile = 'sessions.json';
$sessions = [];
if (file_exists($sessionsFile)) {
    $sessionsData = file_get_contents($sessionsFile);
    $sessions = json_decode($sessionsData, true) ?? [];
}

// Process each user to check device binding status
$totalUsers = count($users);
$boundDevices = 0;
$notBound = 0;
$activeDevices = 0;

foreach ($users as &$user) {
    // Get user's xtream credentials
    $stmt = $pdo->prepare("SELECT xtream_username, xtream_password FROM xtream_codes WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $xtreamData = $stmt->fetch();
    
    if ($xtreamData) {
        // Generate account key (same as in auth_playlist.php)
        $accountKey = md5($xtreamData['xtream_username'] . ':' . $xtreamData['xtream_password']);
        
        // Check if this account has a device binding
        if (isset($sessions[$accountKey])) {
            $session = $sessions[$accountKey];
            $user['account_key'] = $accountKey;
            $user['device_info'] = $session['ua'];
            $user['ip'] = $session['ip'];
            $user['last_seen'] = $session['last_seen'];
            $user['created'] = $session['created'];
            
            // Determine status based on last seen time
            $timeout = 31556926; // 1 year timeout (same as auth_playlist.php)
            $now = time();
            
            if ($now - $session['last_seen'] <= 120) { // Active if seen in last 2 minutes
                $user['status'] = 'active';
                $activeDevices++;
            } else {
                $user['status'] = 'locked';
            }
            
            $boundDevices++;
        } else {
            $user['status'] = 'not-bound';
            $notBound++;
        }
    } else {
        $user['status'] = 'not-bound';
        $notBound++;
    }
}
unset($user); // Break the reference

// Handle device reset if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_user_id'])) {
    $userId = $_POST['reset_user_id'];
    
    // Find the user in our array
    foreach ($users as $user) {
        if ($user['id'] == $userId && isset($user['account_key'])) {
            // Remove from sessions
            if (isset($sessions[$user['account_key']])) {
                unset($sessions[$user['account_key']]);
                file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));
                
                // Show success message
                $successMessage = "Device binding has been reset for user ID: $userId";
                
                // Refresh the page to show updated data
                header("Location: dd.php?success=" . urlencode($successMessage));
                exit();
            }
            break;
        }
    }
    
    $errorMessage = "Failed to reset device binding for user ID: $userId";
    header("Location: dd.php?error=" . urlencode($errorMessage));
    exit();
}

// Display messages if any
if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}
if (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPTV Device Binding Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --card-bg: #ffffff;
            --body-bg: #f0f2f5;
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
            background-color: var(--darker-bg);
            color: var(--dark-text);
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--dark-border);
        }
        
        h1 {
            color: var(--accent-blue);
            font-size: 28px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--dark-bg);
            border: 1px solid var(--dark-border);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 16px;
            color: var(--github-gray);
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: var(--accent-blue);
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background-color: var(--dark-bg);
            border: 1px solid var(--dark-border);
            padding: 15px;
            border-radius: 8px;
        }
        
        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-item label {
            font-size: 14px;
            color: var(--github-gray);
        }
        
        input, select {
            padding: 8px 12px;
            background-color: var(--darker-bg);
            border: 1px solid var(--dark-border);
            border-radius: 5px;
            font-size: 14px;
            color: var(--dark-text);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--dark-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--dark-border);
        }
        
        thead {
            background-color: var(--dark-bg);
            border-bottom: 1px solid var(--dark-border);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--dark-border);
            color: var(--dark-text);
        }
        
        th {
            font-weight: 600;
            cursor: pointer;
            color: var(--accent-blue);
        }
        
        th:hover {
            background-color: var(--darker-bg);
        }
        
        tbody tr:hover {
            background-color: rgba(47, 129, 247, 0.1);
        }
        
        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .active {
            background-color: rgba(76, 201, 240, 0.2);
            color: #4cc9f0;
        }
        
        .locked {
            background-color: rgba(247, 37, 133, 0.2);
            color: #f72585;
        }
        
        .not-bound {
            background-color: rgba(108, 117, 125, 0.2);
            color: var(--github-gray);
        }
        
        .device-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .device-icon {
            font-size: 18px;
            color: var(--github-gray);
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .view-btn {
            background-color: var(--accent-blue);
            color: white;
        }
        
        .view-btn:hover {
            background-color: #1c6ed8;
        }
        
        .reset-btn {
            background-color: var(--warning);
            color: white;
        }
        
        .reset-btn:hover {
            background-color: #e4850c;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }
        
        .pagination button {
            padding: 8px 15px;
            background-color: var(--dark-bg);
            border: 1px solid var(--dark-border);
            border-radius: 5px;
            cursor: pointer;
            color: var(--dark-text);
        }
        
        .pagination button.active {
            background-color: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
        }
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            .filters {
                flex-direction: column;
            }
        }
        
        .refresh-btn {
            background-color: var(--accent-blue);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .refresh-btn:hover {
            background-color: #1c6ed8;
        }
        
        .logout-btn {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }
        
        .logout-btn:hover {
            background-color: #d3126f;
        }
        
        .last-updated {
            color: var(--github-gray);
            font-size: 14px;
            margin-top: 15px;
            text-align: center;
        }
        
        .user-count {
            margin-top: 15px;
            color: var(--github-gray);
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: var(--dark-bg);
            margin: 10% auto;
            padding: 20px;
            border: 1px solid var(--dark-border);
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
        }
        
        .close {
            color: var(--github-gray);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--dark-text);
        }
        
        .device-details {
            margin-top: 20px;
        }
        
        .detail-item {
            margin-bottom: 10px;
            display: flex;
        }
        
        .detail-label {
            font-weight: bold;
            min-width: 120px;
            color: var(--accent-blue);
        }
        
        .detail-value {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-lock"></i> IPTV Device Binding Dashboard</h1>
            <div>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="dd.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card">
                <h3>Devices Bound</h3>
                <div class="value"><?php echo $boundDevices; ?></div>
            </div>
            <div class="stat-card">
                <h3>Not Yet Bound</h3>
                <div class="value"><?php echo $notBound; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Devices</h3>
                <div class="value"><?php echo $activeDevices; ?></div>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-item">
                <label for="search">Search</label>
                <input type="text" id="search" placeholder="Search by username, device, IP...">
            </div>
            <div class="filter-item">
                <label for="status-filter">Status</label>
                <select id="status-filter">
                    <option value="all">All Users</option>
                    <option value="bound">Device Bound</option>
                    <option value="not-bound">Not Bound</option>
                    <option value="active">Active</option>
                    <option value="locked">Locked</option>
                </select>
            </div>
            <div class="filter-item">
                <label for="device-filter">Device Type</label>
                <select id="device-filter">
                    <option value="all">All Devices</option>
                    <option value="kodi">Kodi</option>
                    <option value="android">Android</option>
                    <option value="ios">iOS</option>
                </select>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>User ID <i class="fas fa-sort"></i></th>
                    <th>Username <i class="fas fa-sort"></i></th>
                    <th>Device Info <i class="fas fa-sort"></i></th>
                    <th>IP Address <i class="fas fa-sort"></i></th>
                    <th>Last Seen <i class="fas fa-sort"></i></th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td>
                        <?php if (isset($user['device_info']) && $user['device_info']): ?>
                        <div class="device-info">
                            <?php if (strpos($user['device_info'], 'Kodi') !== false): ?>
                            <i class="fab fa-linux device-icon"></i>
                            <?php elseif (strpos($user['device_info'], 'Android') !== false): ?>
                            <i class="fab fa-android device-icon"></i>
                            <?php elseif (strpos($user['device_info'], 'iOS') !== false): ?>
                            <i class="fab fa-apple device-icon"></i>
                            <?php else: ?>
                            <i class="fas fa-desktop device-icon"></i>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($user['device_info']); ?></span>
                        </div>
                        <?php else: ?>
                        <div class="device-info">
                            <i class="fas fa-question device-icon"></i>
                            <span>No device bound</span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo isset($user['ip']) ? htmlspecialchars($user['ip']) : '-'; ?></td>
                    <td>
                        <?php if (isset($user['last_seen']) && $user['last_seen']): ?>
                        <?php echo date('Y-m-d H:i', $user['last_seen']); ?>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($user['status'])): ?>
                        <span class="status <?php echo $user['status']; ?>">
                            <?php 
                            if ($user['status'] == 'active') echo 'Active';
                            elseif ($user['status'] == 'locked') echo 'Locked';
                            else echo 'Not Bound';
                            ?>
                        </span>
                        <?php else: ?>
                        <span class="status not-bound">Not Bound</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($user['account_key']) && $user['account_key']): ?>
                        <button class="action-btn view-btn" onclick="viewDeviceInfo(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo isset($user['device_info']) ? htmlspecialchars($user['device_info']) : ''; ?>', '<?php echo isset($user['ip']) ? htmlspecialchars($user['ip']) : ''; ?>', '<?php echo isset($user['last_seen']) ? date('Y-m-d H:i', $user['last_seen']) : ''; ?>', '<?php echo isset($user['created']) ? date('Y-m-d H:i', $user['created']) : ''; ?>')">View</button>
                        <form method="POST" action="dd.php" style="display: inline;">
                            <input type="hidden" name="reset_user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="action-btn reset-btn" onclick="return confirm('Are you sure you want to reset the device binding for <?php echo htmlspecialchars($user['username']); ?>?')">Reset</button>
                        </form>
                        <?php else: ?>
                        <button class="action-btn view-btn" disabled>View</button>
                        <button class="action-btn reset-btn" disabled>Reset</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <button class="active">1</button>
            <button>2</button>
            <button>3</button>
        </div>
        
        <div class="last-updated" id="last-updated">Last updated: <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>

    <!-- Device Info Modal -->
    <div id="deviceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Device Information</h2>
            <div id="modalUsername" class="device-details"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Simple filtering functionality
            const searchInput = document.getElementById('search');
            const statusFilter = document.getElementById('status-filter');
            const deviceFilter = document.getElementById('device-filter');
            const tableRows = document.querySelectorAll('tbody tr');
            
            function filterTable() {
                const searchText = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const deviceValue = deviceFilter.value;
                
                tableRows.forEach(row => {
                    const usernameText = row.cells[1].textContent.toLowerCase();
                    const deviceText = row.cells[2].textContent.toLowerCase();
                    const ipText = row.cells[3].textContent.toLowerCase();
                    const statusClass = row.cells[5].querySelector('.status').classList;
                    
                    let statusMatch = false;
                    if (statusValue === 'all') statusMatch = true;
                    else if (statusValue === 'bound' && !statusClass.contains('not-bound')) statusMatch = true;
                    else if (statusValue === 'not-bound' && statusClass.contains('not-bound')) statusMatch = true;
                    else if (statusValue === 'active' && statusClass.contains('active')) statusMatch = true;
                    else if (statusValue === 'locked' && statusClass.contains('locked')) statusMatch = true;
                    
                    const deviceType = deviceText.includes('ubuntu') || deviceText.includes('kodi') ? 'kodi' : 
                                      deviceText.includes('android') ? 'android' :
                                      deviceText.includes('ios') ? 'ios' : 'other';
                    
                    const matchesSearch = usernameText.includes(searchText) || 
                                         deviceText.includes(searchText) || 
                                         ipText.includes(searchText);
                    const matchesDevice = deviceValue === 'all' || 
                                         (deviceValue === 'kodi' && deviceType === 'kodi') ||
                                         (deviceValue === 'android' && deviceType === 'android') ||
                                         (deviceValue === 'ios' && deviceType === 'ios');
                    
                    if (matchesSearch && statusMatch && matchesDevice) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
            deviceFilter.addEventListener('change', filterTable);
            
            // Sort functionality
            const tableHeaders = document.querySelectorAll('th');
            
            tableHeaders.forEach((header, index) => {
                header.addEventListener('click', () => {
                    // Simple toggle for demo (not actual sorting implementation)
                    const icon = header.querySelector('i');
                    if (icon) {
                        if (icon.classList.contains('fa-sort')) {
                            icon.classList.replace('fa-sort', 'fa-sort-up');
                        } else if (icon.classList.contains('fa-sort-up')) {
                            icon.classList.replace('fa-sort-up', 'fa-sort-down');
                        } else {
                            icon.classList.replace('fa-sort-down', 'fa-sort-up');
                        }
                    }
                });
            });
            
            // Update last updated time
            function updateLastUpdated() {
                const now = new Date();
                document.getElementById('last-updated').textContent = 
                    `Last updated: ${now.toLocaleTimeString()}`;
            }
            
            setInterval(updateLastUpdated, 60000);
        });
        
        function viewDeviceInfo(userId, username, deviceInfo, ip, lastSeen, created) {
            const modal = document.getElementById('deviceModal');
            const modalUsername = document.getElementById('modalUsername');
            
            modalUsername.innerHTML = `
                <div class="detail-item">
                    <span class="detail-label">User ID:</span>
                    <span class="detail-value">${userId}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Username:</span>
                    <span class="detail-value">${username}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Device Info:</span>
                    <span class="detail-value">${deviceInfo || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">IP Address:</span>
                    <span class="detail-value">${ip || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Last Seen:</span>
                    <span class="detail-value">${lastSeen || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value">${created || 'N/A'}</span>
                </div>
            `;
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            const modal = document.getElementById('deviceModal');
            modal.style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('deviceModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
