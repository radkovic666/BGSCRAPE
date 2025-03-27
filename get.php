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
    // Set the correct content type for M3U
    header("Content-Type: application/x-mpegurl; charset=utf-8");

    // Output the M3U header with the correct EPG URL
    echo "#EXTM3U tvg-id=\"https://epg.cloudns.org/dl.php\"\n";

    // Read and output the M3U playlist
    $playlist_url = "http://88.203.24.111/playlist.m3u"; // Change if needed
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
