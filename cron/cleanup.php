<?php

require_once __DIR__ . '/../config/credentials.php';
$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../utils/MemoryManager.php';
require_once __DIR__ . '/../utils/Logger.php';

$memory = new MemoryManager($config);
$logger = new Logger($config['paths']['logs']);

echo "=== Memory Cleanup ===\n";

$deleted = $memory->cleanup();

echo "Deleted {$deleted} expired memory files\n";
$logger->info("Memory cleanup completed", ['deleted' => $deleted]);

echo "=== Cleanup finished ===\n";
