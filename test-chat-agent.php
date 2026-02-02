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
    $data = json_decode(file_get_contents($memFile), true);
    echo "Counter: " . ($data['counter'] ?? 'N/A') . "\n";
    echo "Recent Messages: " . count($data['recent_messages']) . "\n";
} else {
    echo "❌ Memory file NOT found!\n";
    exit(1);
}

// 3. Send 9 more messages to trigger summary (Total 11 interactions? No, initial command cleared it. 
// handleMessage increments counter. 
// We sent 1 message (User) -> +1 counter? 
// Wait, handleMessage usually adds User + Assistant. 
// Let's check MemoryManager.
// handleMessage: 
//   addRecentMessage(user)
//   addRecentMessage(assistant)
//   incrementCounter() -> This increments ONCE per turn. 
// So 1 turn = 1 counter increment.
// Logic: if ($counter > 0 && $counter % 10 == 0) summarize.

echo "\n[3] Sending 9 more messages to trigger summarization...\n";
for ($i = 2; $i <= 10; $i++) {
    echo "Sending message $i...\n";
    $agent->handleMessage($testChatId, "Laporan ke-$i: Semuanya aman.");
    // fast sleep to avoid rate limits if any, though Groq is fast
    // With 6000 TPM and ~1.5k tokens per request, we can do ~4 requests/min.
    // Sleep 15s to be safe.
    echo "Sleeping 15s to avoid Rate Limit...\n";
    sleep(15); 
}

// 4. Verify Summary after 10th message
echo "\n[4] Verifying Summary generation...\n";
$data = json_decode(file_get_contents($memFile), true);

echo "Final Counter: " . $data['counter'] . "\n";
echo "Recent Messages Count: " . count($data['recent_messages']) . "\n";

if (!empty($data['summary'])) {
    echo "✅ Summary exists: " . json_encode($data['summary']) . "\n";
} else {
    echo "❌ Summary MISSING (Should have generated at counter 10)\n";
}

if (count($data['recent_messages']) <= 4) {
    echo "✅ Recent messages trimmed correctly (Count: " . count($data['recent_messages']) . ")\n";
} else {
    echo "⚠️ Recent messages count seems high: " . count($data['recent_messages']) . " (Expected <= 4)\n";
}

echo "\n=== Test Complete ===\n";
