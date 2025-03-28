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
    "bango" => "vasil",
    "u4o7p" => "h3j1k"
];

// Path to a file that stores device identifiers for users
$usage_file = 'user_device_usage.json';

// Get parameters from URL
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
$type = $_GET['type'] ?? '';

// Validate user credentials
if (isset($users[$username]) && $users[$username] === $password) {
    // Generate or retrieve unique device identifier (token)
    $device_token = $_GET['device_token'] ?? ''; // Expect a device token from the client

    if ($device_token == '') {
        // If no device token is provided, generate one for new users
        $device_token = uniqid($username . '-', true); // Generate a unique ID based on username
    }

    // Retrieve stored usage data (which device is logged in with each username)
    $usage_data = file_exists($usage_file) ? json_decode(file_get_contents($usage_file), true) : [];

    // Check if the username is already tied to a device
    if (isset($usage_data[$username]) && $usage_data[$username] !== $device_token) {
        // The user is already logged in on a different device
        echo "This username is already in use on another device.";
        exit();
    }

    // Store the username with the device token (bind the username to the device)
    $usage_data[$username] = $device_token;
    file_put_contents($usage_file, json_encode($usage_data));

    // Set the correct content type for M3U
    header("Content-Type: application/x-mpegurl; charset=utf-8");

    // Output the M3U header with the correct EPG URL
    echo "#EXTM3U tvg-id=\"https://epg.cloudns.org/dl.php\"\n";

    // Read and output the M3U playlist
    $playlist_url = "http://nyama.fun/playlist.m3u"; // Change if needed
    $playlist_content = file_get_contents($playlist_url);

    if ($playlist_content !== false) {
        echo $playlist_content;
    } else {
        echo "#EXTINF:-1,ERROR: Playlist not found\n";
    }
    exit();
} else {
    // Invalid credentials
    echo "Invalid username or password.";
}
?>
