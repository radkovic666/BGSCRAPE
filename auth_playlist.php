<?php
require 'config.php';

$usage_file = 'user_device_usage.json';
$token_expiration = 86400; // 24 hours

// Validate parameters
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(403);
    die("Access denied. Credentials required.");
}

// Authenticate user
$stmt = $pdo->prepare("SELECT xtream_password FROM xtream_codes WHERE xtream_username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['xtream_password'] !== $password) {
    http_response_code(403);
    die("Invalid credentials");
}

// Device tracking
$current_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device_id = md5($current_ip . $user_agent);

$usage_data = file_exists($usage_file) ? json_decode(file_get_contents($usage_file), true) : [];
$current_time = time();

// Check existing usage
if (isset($usage_data[$username])) {
    $stored = $usage_data[$username];
    $time_diff = $current_time - $stored['last_active'];

    // Device changed and session still active
    if ($stored['device_id'] !== $device_id && $time_diff < $token_expiration) {
        http_response_code(403);
        die("Account is in use on another device.");
    }

    // Update device info
    $usage_data[$username] = [
        'device_id' => $device_id,
        'last_active' => $current_time
    ];
} else {
    // New entry
    $usage_data[$username] = [
        'device_id' => $device_id,
        'last_active' => $current_time
    ];
}

file_put_contents($usage_file, json_encode($usage_data, JSON_PRETTY_PRINT));

// Generate fresh playlist from database
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Content-Type: application/x-mpegurl");
echo "#EXTM3U\n";

$stmt = $pdo->prepare("SELECT channel_name, stream_url, tvg_id, tvg_logo, group_title FROM channels ORDER BY id ASC");
$stmt->execute();
$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($channels as $channel) {
    echo "#EXTINF:-1 tvg-id=\"{$channel['tvg_id']}\" tvg-logo=\"{$channel['tvg_logo']}\" group-title=\"{$channel['group_title']}\",{$channel['channel_name']}\n";
    echo "{$channel['stream_url']}\n";
}
?>