<?php

require_once __DIR__ . '/config/credentials.php';
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/MemoryManager.php';

echo "=== Memory Encryption Migration ===\n";

$memoryPath = $config['paths']['memory'];
$files = glob($memoryPath . 'user_*.json');
$count = 0;
$migrated = 0;
$skipped = 0;

if (empty($files)) {
    echo "No memory files found to migrate.\n";
    exit;
}

$memoryManager = new MemoryManager($config);

foreach ($files as $file) {
    echo "Processing: " . basename($file) . " ... ";
    
    // Extract User ID
    $filename = basename($file);
    if (!preg_match('/user_(\d+)\.json/', $filename, $matches)) {
        echo "[SKIP] Invalid filename format.\n";
        continue;
    }
    
    $userId = $matches[1];
    
    // Check if already encrypted to avoid double work (though MemoryManager handles it safely)
    // We can read the file raw content
    $rawContent = file_get_contents($file);
    $isJson = json_decode($rawContent);
    
    // If it is valid JSON (object/array), it is NOT encrypted/base64 encoded string format we use
    // Our encrypted format is base64 string "..."
    // json_decode("base64...") returns string or null if not quoted properly.
    // If json_decode returns object/array, it is DEFINITELY legacy plain text.
    
    $needsMigration = false;
    if (is_array($isJson) || is_object($isJson)) {
        $needsMigration = true;
    } else {
        // It might be encrypted or just invalid.
        // Let's verify if strictly encrypted.
        // Our MemoryManager::load will try decrypt.
        // If we want to be forceful, we just load and save.
        // But let's log status.
        // If raw content starts with characters typical of JSON like '{', likely needs migration
        if (trim($rawContent)[0] === '{') {
             $needsMigration = true;
        }
    }
    
    if ($needsMigration) {
        try {
            // Load (handles decryption or legacy)
            $memoryManager->load($userId);
            
            // Save (forces encryption)
            $memoryManager->save();
            
            echo "[OK] Migrated to encrypted format.\n";
            $migrated++;
        } catch (Exception $e) {
            echo "[ERROR] Failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "[SKIP] Already encrypted or invalid format.\n";
        $skipped++;
    }
    
    $count++;
}

echo "\nSummary:\n";
echo "Total Files Scanned: $count\n";
echo "Migrated: $migrated\n";
echo "Skipped (Already Encrypted): $skipped\n";
