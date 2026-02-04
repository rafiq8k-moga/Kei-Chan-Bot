<?php
require_once __DIR__ . '/config/credentials.php';
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/agents/ChatAgent.php';
require_once __DIR__ . '/utils/Logger.php';

// Use a test chat ID
$testChatId = 7019860603; 

echo "=== ChatAgent Verification Test ===\n";

$agent = new ChatAgent($config);

// 1. Reset Memory
echo "\n[1] Sending /mulai to reset memory...\n";
$agent->handleCommand($testChatId, '/mulai');
sleep(1);

// 2. Send 1st message
echo "\n[2] Sending first message...\n";
$agent->handleMessage($testChatId, "Halo Kei, aku mau laporan soal proyek.");
sleep(2);

// Verify memory file creation
$memFile = $config['paths']['memory'] . 'user_' . $testChatId . '.json';
if (file_exists($memFile)) {
    echo "✅ Memory file created.\n";
    
    // 1. Verify Encryption (Raw file should NOT be valid JSON or should be the base64 format)
    $rawContent = file_get_contents($memFile);
    $decodedRaw = json_decode($rawContent, true);
    
    // valid encrypted data is NOT a json array with 'user_id' etc at root usually, 
    // unless the encrypted string happens to be valid json (unlikely).
    // Our encryption format is base64 string. json_decode("base64...") returns string or null.
    // If it was plain json, it would return array.
    if (is_array($decodedRaw) && isset($decodedRaw['user_id'])) {
         echo "❌ Memory file is NOT encrypted! (Found plain JSON)\n";
    } else {
         echo "✅ Memory file appears encrypted (Raw content is not plain user JSON).\n";
    }

    // 2. Verify Decryption via MemoryManager
    // We need to reload to test decryption
    $memManager2 = new MemoryManager($config);
    $data = $memManager2->load($testChatId);
    
    if ($data['user_id'] == $testChatId) {
        echo "✅ Decryption successful. User ID matches.\n";
    } else {
        echo "❌ Decryption FAILED.\n";
    }
    
    echo "Counter: " . ($data['counter'] ?? 'N/A') . "\n";
    echo "Recent Messages: " . count($data['recent_messages']) . "\n";
} else {
    echo "❌ Memory file NOT found!\n";
    exit(1);
}

// 3. Send 9 more messages to trigger summary
echo "\n[3] Sending 9 more messages to trigger summarization...\n";
for ($i = 2; $i <= 10; $i++) {
    echo "Sending message $i...\n";
    $agent->handleMessage($testChatId, "Laporan ke-$i: Semuanya aman.");
    // fast sleep to avoid rate limits
    // sleep(1); 
}

// 4. Verify Summary after 10th message
echo "\n[4] Verifying Summary generation...\n";
// Reload again to get latest
$data = $memManager2->load($testChatId);

echo "Final Counter: " . $data['counter'] . "\n";
echo "Recent Messages Count: " . count($data['recent_messages']) . "\n";

if (!empty($data['summary'])) {
    echo "✅ Summary exists: " . json_encode($data['summary']) . "\n";
} else {
    echo "❌ Summary MISSING (Should have generated at counter 10)\n";
    // It might fail if Groq isn't reachable or quota.
}

if (count($data['recent_messages']) <= 4) {
    echo "✅ Recent messages trimmed correctly (Count: " . count($data['recent_messages']) . ")\n";
} else {
    echo "⚠️ Recent messages count seems high: " . count($data['recent_messages']) . " (Expected <= 4)\n";
}

echo "\n=== Test Complete ===\n";
