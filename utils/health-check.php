<?php

require_once __DIR__ . '/../config/credentials.php';
$config = require __DIR__ . '/../config/config.php';

echo "=== Facebook Bot Health Check ===\n\n";

// Check storage directories
$checks = [
    'Memory directory writable' => is_writable($config['paths']['memory']),
    'Logs directory writable' => is_writable($config['paths']['logs']),
    'cURL extension loaded' => extension_loaded('curl'),
    'JSON extension loaded' => extension_loaded('json'),
];

foreach ($checks as $check => $status) {
    echo sprintf(
        "[%s] %s\n",
        $status ? '✓' : '✗',
        $check
    );
}

// Count memory files
$memoryFiles = glob($config['paths']['memory'] . 'user_*.json');
echo "\nActive users: " . count($memoryFiles) . "\n";

// Check last post log
$logFiles = glob($config['paths']['logs'] . '*.log');
if (!empty($logFiles)) {
    $latestLog = array_pop($logFiles);
    echo "Latest log: " . basename($latestLog) . "\n";
}

echo "\n=== Health check completed ===\n";
