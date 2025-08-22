<?php
// One-device-at-a-time binding

// ==== CONFIG ====
$playlistFile = __DIR__ . "/playlist.m3u";   // physical playlist
$sessionsFile = __DIR__ . "/sessions.json";  // temp sessions storage
$timeout      = 31556926; // seconds of inactivity before freeing slot (1 year)
// ===============

// Load query params
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';

if (empty($username) || empty($password)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Missing credentials.");
}

// Unique account key
$accountKey = md5($username . ":" . $password);

// Device fingerprint
$clientIp  = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fingerprint = md5($clientIp . $userAgent);

// Load existing sessions
$sessions = [];
if (file_exists($sessionsFile)) {
    $sessions = json_decode(file_get_contents($sessionsFile), true) ?? [];
}

// Check if session exists
$now = time();
if (isset($sessions[$accountKey])) {
    $session = $sessions[$accountKey];

    // If session expired -> free slot
    if ($now - $session['last_seen'] > $timeout) {
        unset($sessions[$accountKey]);
    } else {
        // If another device is using it now -> block
        if ($session['fingerprint'] !== $fingerprint) {
            header("HTTP/1.1 403 Forbidden");
            exit("This playlist is currently in use on another device.");
        }
    }
}

// Register/update session
$sessions[$accountKey] = [
    "fingerprint" => $fingerprint,
    "ip" => $clientIp,
    "ua" => $userAgent,
    "last_seen" => $now
];
file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));

// Serve the physical playlist.m3u file after auth passes
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/x-mpegurl");
readfile($playlistFile);
exit;
?>
