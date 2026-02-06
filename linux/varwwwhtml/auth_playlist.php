<?php
require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ============ CONFIG ============
$playlistFile = __DIR__ . "/playlist.m3u";
// =================================

// Read credentials
$reqUser = $_GET['username'] ?? '';
$reqPass = $_GET['password'] ?? '';

if ($reqUser === '' || $reqPass === '') {
    http_response_code(400);
    exit("Missing credentials");
}

// IMPORTANT: Xtream passwords are ALREADY MD5
$passHash = $reqPass;

// Client info
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$accountKey = md5($reqUser . ':' . $passHash);
$fingerprint = md5($accountKey . '|' . $userAgent);

// ================= AUTH =================
$stmt = $pdo->prepare("
    SELECT 
        u.id AS user_id,
        u.status,
        x.is_active
    FROM xtream_codes x
    JOIN users u ON u.id = x.user_id
    WHERE x.xtream_username = :u
      AND x.xtream_password = :p
    LIMIT 1
");
$stmt->execute([
    ':u' => $reqUser,
    ':p' => $passHash
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    exit("Invalid username or password");
}

if ($user['status'] !== 'active' || !$user['is_active']) {
    http_response_code(403);
    exit("Account disabled");
}

$userId = (int)$user['user_id'];

// ================= DEVICE BINDING =================
$stmt = $pdo->prepare("
    SELECT * FROM device_bindings WHERE user_id = :uid LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$binding = $stmt->fetch(PDO::FETCH_ASSOC);

if ($binding) {
    if ($binding['device_fingerprint'] !== $fingerprint) {
        http_response_code(403);
        exit("Account already bound to another device");
    }

    $pdo->prepare("
        UPDATE device_bindings
        SET last_seen = NOW(), ip_address = :ip
        WHERE user_id = :uid
    ")->execute([
        ':ip' => $ip,
        ':uid' => $userId
    ]);
} else {
    $pdo->prepare("
        INSERT INTO device_bindings
        (user_id, account_key, device_fingerprint, user_agent, ip_address)
        VALUES (:uid, :akey, :fp, :ua, :ip)
    ")->execute([
        ':uid' => $userId,
        ':akey' => $accountKey,
        ':fp' => $fingerprint,
        ':ua' => $userAgent,
        ':ip' => $ip
    ]);
}

// ================= SERVE PLAYLIST =================
if (!file_exists($playlistFile)) {
    http_response_code(500);
    exit("Playlist missing");
}

header("Content-Type: application/x-mpegurl");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

readfile($playlistFile);
exit;
