<?php
// ==== CONFIG ====
$playlistFile = __DIR__ . "/playlist.m3u";
$sessionsFile = __DIR__ . "/sessions.json";
$timeout = 31556926; // 1 year timeout
// ===============

// Load query params
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (empty($username) || empty($password)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Missing credentials.");
}

// Create unique account identifier
$accountKey = md5($username . ":" . $password);

// Create device fingerprint (username+password+user agent)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fingerprint = md5($accountKey . "|" . $userAgent);

// Load existing sessions
$sessions = [];
if (file_exists($sessionsFile)) {
    $sessions = json_decode(file_get_contents($sessionsFile), true) ?? [];
}

$now = time();

// Cleanup expired sessions
foreach ($sessions as $key => $session) {
    if ($now - $session['last_seen'] > $timeout) {
        unset($sessions[$key]);
    }
}

// Check if this account already has a device binding
if (isset($sessions[$accountKey])) {
    $session = $sessions[$accountKey];
    
    // If same device → refresh last_seen
    if ($session['fingerprint'] === $fingerprint) {
        $sessions[$accountKey]['last_seen'] = $now;
    } else {
        // Different device → BLOCK access
        header("HTTP/1.1 403 Forbidden");
        exit("This playlist is locked to another device. Please use the original device or contact support to reset your device binding.");
    }
} else {
    // First time this account is used → create device binding
    $sessions[$accountKey] = [
        "fingerprint" => $fingerprint,
        "ua" => $userAgent,
        "last_seen" => $now,
        "ip" => $_SERVER['REMOTE_ADDR'],
        "created" => $now
    ];
}

// Save sessions
file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));

// Serve playlist only if device is authorized
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/x-mpegurl");
readfile($playlistFile);
exit;
?>
