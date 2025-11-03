<?php
// ====================================================================
// ip_tracker.php
// Dynamic Public IP Tracker + Email Alert via smtp.abv.bg
// ====================================================================

// --- CONFIGURATION ---
$logFile = __DIR__ . '/ip_history.txt';
$senderEmail = 'nyamafun@abv.bg';
$senderPassword = 'H0rnbow12'; // replace locally with your real password
$receiverEmail = 'dragomir.d.dimitrov@abv.bg'; // corrected typo

// --- INCLUDE PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '/var/www/html/PHPMailer/src/Exception.php';
require_once '/var/www/html/PHPMailer/src/PHPMailer.php';
require_once '/var/www/html/PHPMailer/src/SMTP.php';

// --- FUNCTION: Get current public IP ---
function getPublicIP() {
    $sources = [
        'https://ifconfig.me/ip',
        'https://api.ipify.org',
        'https://checkip.amazonaws.com'
    ];

    foreach ($sources as $url) {
        $ip = @file_get_contents($url);
        if ($ip && filter_var(trim($ip), FILTER_VALIDATE_IP)) {
            return trim($ip);
        }
    }
    return null;
}

// --- FUNCTION: Get last logged IP ---
function getLastIP($file) {
    if (!file_exists($file)) return null;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return null;

    $lastLine = trim(end($lines));
    if (strpos($lastLine, ' - ') !== false) {
        $parts = explode(' - ', $lastLine);
        return trim(end($parts));
    }
    return null;
}

// --- FUNCTION: Log new IP ---
function logNewIP($file, $ip) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($file, "$timestamp - $ip\n", FILE_APPEND | LOCK_EX);
}

// --- FUNCTION: Send email ---
function sendIPChangeEmail($fromEmail, $fromPass, $toEmail, $newIP) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.abv.bg';
        $mail->SMTPAuth = true;
        $mail->Username = $fromEmail;
        $mail->Password = $fromPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom($fromEmail, 'IP Tracker');
        $mail->addAddress($toEmail);
        $mail->Subject = 'Public IP Changed';
        $mail->Body = "Your public IP has changed.\n\nNew IP: $newIP\nTime: " . date('Y-m-d H:i:s');

        $mail->send();
        error_log("IP change email successfully sent to $toEmail");
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
    }
}

// --- MAIN EXECUTION ---
$currentIP = getPublicIP();

if ($currentIP) {
    $lastIP = getLastIP($logFile);

    if ($currentIP !== $lastIP) {
        logNewIP($logFile, $currentIP);
        sendIPChangeEmail($senderEmail, $senderPassword, $receiverEmail, $currentIP);
    }
} else {
    error_log("Unable to fetch current public IP");
}

echo "IP monitoring completed.\n";
?>
