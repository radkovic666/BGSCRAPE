<?php
require 'config.php';

$device_bindings_file = 'device_bindings.json';

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

// Create a unique device fingerprint
$device_fingerprint = md5(
    $_SERVER['HTTP_USER_AGENT'] . 
    $_SERVER['REMOTE_ADDR'] . 
    (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '') .
    (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '')
);

// Create a URL identifier
$url_identifier = md5($username . $password);

// Load device bindings
$device_bindings = file_exists($device_bindings_file) ? 
    json_decode(file_get_contents($device_bindings_file), true) : [];

// Check if this URL is already bound to a device
if (isset($device_bindings[$url_identifier])) {
    // If bound to a different device, deny access
    if ($device_bindings[$url_identifier]['device_fingerprint'] !== $device_fingerprint) {
        http_response_code(403);
        die("This URL is already bound to another device. Please use the original device or generate a new URL from your dashboard.");
    }
    
    // Update timestamp for the same device
    $device_bindings[$url_identifier]['last_used'] = time();
} else {
    // Bind this URL to the current device
    $device_bindings[$url_identifier] = [
        'device_fingerprint' => $device_fingerprint,
        'username' => $username,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'bound_at' => time(),
        'last_used' => time()
    ];
}

// Save device bindings
file_put_contents($device_bindings_file, json_encode($device_bindings, JSON_PRETTY_PRINT));

// Serve the physical playlist.m3u file after auth passes
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Content-Type: application/x-mpegurl");

// Output the physical file contents
readfile('playlist.m3u');

?>
