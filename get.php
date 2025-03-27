<?php
// User credentials (manually managed or fetched from a database)
$users = [
    "usr41" => "p3s9k",
    "a92jf" => "l8md2",
    "x5y2z" => "k9p4v",
    "q7w8e" => "r3t2y",
    "m3n4b" => "c6x1z",
    "p9l8o" => "v2b5n",
    "g7h2k" => "s4m3c",
    "d1j5f" => "q8w6e",
    "z3x2v" => "y5t9r",
    "u4o7p" => "h3j1k"
];

// Get parameters from URL
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
$type = $_GET['type'] ?? '';

// Validate user credentials
if (isset($users[$username]) && $users[$username] === $password) {
    // Redirect to your GitHub M3U file
    header("Location: https://raw.githubusercontent.com/radkovic666/bgtv/refs/heads/main/playlist.m3u");
    exit();
} else {
    // Invalid credentials
    echo "Invalid username or password.";
}
?>
