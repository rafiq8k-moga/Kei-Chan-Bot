<?php
// Load configuration
require_once __DIR__ . '/config/credentials.php';
$config = require __DIR__ . '/config/config.php';

echo "=== Telegram Bot Connection Test (cURL Version) ===\n";

$token = getenv('TELEGRAM_BOT_TOKEN');
if (!$token) {
    // Try to load from config directly if env not set
    $token = $config['telegram']['bot_token'] ?? null;
}

// Masked token for debug
$maskedToken = substr($token, 0, 5) . '...' . substr($token, -5);
echo "Using Token: $maskedToken\n";

if (empty($token) || strpos($token, 'YOUR_') !== false) {
    echo "❌ Error: Token is invalid or placeholder. Please update config/credentials.php\n";
    exit(1);
}

// URL for getMe
$url = "https://api.telegram.org/bot$token/getMe";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL check for testing if needed
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "Sending request to: https://api.telegram.org/bot[HIDDEN]/getMe ...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "❌ cURL Error: $error\n";
    exit(1);
}

echo "HTTP Code: $httpCode\n";
echo "Raw Response: $response\n";

$data = json_decode($response, true);

if ($httpCode == 200 && ($data['ok'] ?? false)) {
    echo "\n✅ Authentication Successful!\n";
    echo "Bot ID: " . $data['result']['id'] . "\n";
    echo "Bot Name: " . $data['result']['first_name'] . "\n";
    echo "Username: @" . $data['result']['username'] . "\n";
} else {
    echo "\n❌ Authentication Failed!\n";
    echo "Description: " . ($data['description'] ?? 'No description') . "\n";
}
