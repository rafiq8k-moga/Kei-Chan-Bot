<?php

/**
 * Broadcast info from info.txt to all Telegram users in storage/memory
 */

// Load credentials
require_once __DIR__ . '/config/credentials.php';

// Load configuration
$config = require_once __DIR__ . '/config/config.php';

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Load Telegram API
require_once __DIR__ . '/services/TelegramAPI.php';
$telegram = new TelegramAPI($config);

// Path to memory storage
$memoryPath = $config['paths']['memory'];
$infoFile = __DIR__ . '/info.txt';

if (!file_exists($infoFile)) {
    die("Error: info.txt not found.\n");
}

// Read info content
$infoContent = file_get_contents($infoFile);
$infoContent = trim($infoContent);

if (empty($infoContent)) {
    die("Error: info.txt is empty.\n");
}

// Find all user files
$files = glob($memoryPath . 'user_*.json');
$userIds = [];

foreach ($files as $file) {
    if (preg_match('/user_(\d+)\.json/', basename($file), $matches)) {
        $userIds[] = $matches[1];
    }
}

$userIds = array_unique($userIds);

if (empty($userIds)) {
    die("No users found in $memoryPath\n");
}

echo "Broadcasting message to " . count($userIds) . " users.\n";
echo "Message Content:\n------------------\n$infoContent\n------------------\n";
echo "Do you want to continue? (y/n): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim(strtolower($line)) != 'y') {
    echo "Broadcast cancelled.\n";
    exit;
}

echo "Starting broadcast...\n";

foreach ($userIds as $chatId) {
    echo "Sending to $chatId... ";
    
    // Send as plain text to preserve formatting and avoid Markdown errors
    $result = $telegram->sendMessage($chatId, $infoContent, [
        'parse_mode' => null 
    ]);
    
    if (isset($result['ok']) && $result['ok']) {
        echo "[OK]\n";
    } else {
        echo "[FAILED] - " . ($result['description'] ?? 'Unknown error') . "\n";
    }
    
    // Small delay to avoid hitting Telegram API rate limits if there are many users
    usleep(50000); // 50ms
}

echo "\nBroadcast finished.\n";
