<?php
require 'config.php';

$playlist_file = 'playlist.m3u';

if (!file_exists($playlist_file)) {
    die("Error: playlist.m3u file not found.");
}

$playlist_content = file($playlist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Clear the table before inserting new data
$pdo->exec("TRUNCATE TABLE channels");

$channels = [];
$current_channel = [];

foreach ($playlist_content as $line) {
    if (strpos($line, '#EXTINF:') !== false) {
        preg_match('/tvg-id="([^"]+)" tvg-logo="([^"]+)" group-title="([^"]+)",(.+)/', $line, $matches);
        if (count($matches) === 5) {
            $current_channel = [
                'tvg_id' => $matches[1],
                'tvg_logo' => $matches[2],
                'group_title' => $matches[3],
                'channel_name' => $matches[4],
                'stream_url' => '' // Will be filled in next line
            ];
        }
    } elseif (!empty($current_channel)) {
        $current_channel['stream_url'] = trim($line);
        $channels[] = $current_channel;
        $current_channel = []; // Reset for the next channel
    }
}

// Insert into the database
$stmt = $pdo->prepare("INSERT INTO channels (channel_name, stream_url, tvg_id, tvg_logo, group_title) VALUES (?, ?, ?, ?, ?)");

foreach ($channels as $channel) {
    $stmt->execute([
        $channel['channel_name'],
        $channel['stream_url'],
        $channel['tvg_id'],
        $channel['tvg_logo'],
        $channel['group_title']
    ]);
}

echo "Playlist imported successfully.";
?>
