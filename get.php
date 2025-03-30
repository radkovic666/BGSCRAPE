<?php
require 'config.php';

$usage_file = 'user_device_usage.json';
$token_expiration = 86400; // 24 hours (compromise for EPG refresh limitations)

// Get all potential parameters
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
$device_token = $_GET['device_token'] ?? '';
$epg_request = isset($_GET['epg']); // Detect EPG refresh calls

// Validate credentials
$stmt = $pdo->prepare("SELECT xtream_password FROM xtream_codes WHERE xtream_username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['xtream_password'] !== $password) {
    die("Invalid credentials");
}

// Load usage data
$usage_data = file_exists($usage_file) ? json_decode(file_get_contents($usage_file), true) : [];

// Auto-convert legacy format
foreach ($usage_data as $key => &$entry) {
    if (!is_array($entry)) {
        $entry = ['token' => $entry, 'last_active' => time()]; // Give legacy tokens new life
    }
}
unset($entry);

// Token management
$current_time = time();
if (isset($usage_data[$username])) {
    $stored = $usage_data[$username];
    $time_diff = $current_time - $stored['last_active'];
    
    // Allow token renewal through EPG updates
    if ($epg_request && $time_diff > 3600) { // EPG refresh renews if <24h old
        $usage_data[$username]['last_active'] = $current_time;
    }
    
    // Device check only for playlist requests
    if (!$epg_request) {
        if ($device_token !== $stored['token'] && $time_diff < $token_expiration) {
            die("Account in use elsewhere");
        }
        $usage_data[$username]['last_active'] = $current_time;
    }
} else {
    $device_token = $device_token ?: uniqid($username.'-', true);
    $usage_data[$username] = [
        'token' => $device_token,
        'last_active' => $current_time
    ];
}

file_put_contents($usage_file, json_encode($usage_data, JSON_PRETTY_PRINT));

// Output handling
if ($epg_request) {
    header("Location: https://epg.cloudns.org/dl.php");
    exit();
}

header("Content-Type: application/x-mpegurl");
echo "#EXTM3U tvg-id=\"http://88.203.24.111/xmltv.php?username=$username&password=$password\"\n";
//echo "#EXTM3U tvg-id=\"https://epg.cloudns.org/dl.php\"\n";
echo file_get_contents("http://88.203.24.111/playlist.m3u8") ?: "#EXTINF:-1,Playlist Error";
?>