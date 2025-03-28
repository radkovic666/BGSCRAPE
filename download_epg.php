<?php
$epg_url = "http://epg.cloudns.org/dl.php";
$save_path = __DIR__ . "/epg.xml.gz";
$xml_path = __DIR__ . "/epg.xml";

// Download EPG file
file_put_contents($save_path, fopen($epg_url, 'r'));

// Extract the file
$gz = gzopen($save_path, 'rb');
$xml_content = "";
while (!gzeof($gz)) {
    $xml_content .= gzread($gz, 4096);
}
gzclose($gz);

// Save extracted EPG XML
file_put_contents($xml_path, $xml_content);

echo "EPG updated successfully!";
?>
