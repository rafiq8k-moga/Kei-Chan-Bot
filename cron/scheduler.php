<?php

require_once __DIR__ . '/../config/credentials.php';
$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../agents/AutoPostAgent.php';

// Set timezone
date_default_timezone_set($config['app']['timezone']);

echo "=== Facebook Bot Auto-Post Scheduler ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$agent = new AutoPostAgent($config);
$result = $agent->execute();

if ($result) {
    echo "✓ Post executed successfully\n";
} else {
    echo "✗ Post execution failed\n";
}

echo "\n=== Scheduler finished ===\n";
