<?php
require 'config.php';

// Get the username from session (set this after successful registration or login)
session_start();
$username = $_SESSION['username'] ?? ''; // Assuming username is saved after successful registration

if (empty($username)) {
    echo "User not logged in!";
    exit();
}

// Load user data
$usage_file = 'user_device_usage.json';
$users_data = file_exists($usage_file) ? json_decode(file_get_contents($usage_file), true) : [];

if (!isset($users_data[$username])) {
    echo "User not found!";
    exit();
}

// Fetch Xtream credentials
$xtream_user = $users_data[$username]['xtream_user'];
$xtream_pass = $users_data[$username]['xtream_pass'];

// Display the success message and Xtream credentials
echo "Greetings, $username!<br>";
echo "Your Xtream credentials are:<br>";
echo "Xtream Username: $xtream_user<br>";
echo "Xtream Password: $xtream_pass<br>";
echo "<a href='get.php?username=$username'>Click here to access your playlist.</a>";
?>
