<?php
// ==== CONFIG ====
$playlistFile = __DIR__ . "/playlist.m3u";   // physical playlist
$sessionsFile = __DIR__ . "/sessions.json";  // temp sessions storage
$timeout      = 31556926; // inactivity timeout (1 year)
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

// Device fingerprint (do NOT include IP, so same LAN users don’t conflict)
$userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
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

// Find or create session for this fingerprint
if (isset($sessions[$accountKey])) {
    $session = $sessions[$accountKey];

    // If same fingerprint → just refresh last_seen
    if ($session['fingerprint'] === $fingerprint) {
        $sessions[$accountKey]['last_seen'] = $now;
        $sessions[$accountKey]['ua']        = $userAgent;
    } else {
        // Different device trying to use same account → allow parallel but separate session
        // Key off fingerprint instead of single account key
        $sessions[$accountKey . "_" . substr($fingerprint, 0, 8)] = [
            "fingerprint" => $fingerprint,
            "ua"          => $userAgent,
            "last_seen"   => $now
        ];
    }
} else {
    // First time this account is used → create session
    $sessions[$accountKey] = [
        "fingerprint" => $fingerprint,
        "ua"          => $userAgent,
        "last_seen"   => $now
    ];
}

// Save sessions.json
file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));

// Serve playlist
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/x-mpegurl");
readfile($playlistFile);
exit;
?>
