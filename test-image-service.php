<?php
require_once __DIR__ . '/config/credentials.php';
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/services/ImageService.php';

echo "=== ImageService Test ===\n";

$service = new ImageService($config);

// Test Safebooru
echo "\n[1] Testing Safebooru (SFW)...\n";
$url1 = $service->getRandomImage('safebooru');
echo "URL: " . ($url1 ?? 'NULL') . "\n";
if ($url1 && strpos($url1, 'safebooru') !== false || strpos($url1, 'donmai.us') !== false) {
    echo "✅ Safebooru URL looks valid.\n";
} else {
    echo "❌ Safebooru failed or invalid URL.\n";
}

// Test Danbooru
echo "\n[2] Testing Danbooru (NSFW)...\n";
$url2 = $service->getRandomImage('danbooru');
echo "URL: " . ($url2 ?? 'NULL') . "\n";
if ($url2 && (strpos($url2, 'danbooru') !== false || strpos($url2, 'donmai.us') !== false)) {
    echo "✅ Danbooru URL looks valid.\n";
} else {
    echo "❌ Danbooru failed or invalid URL.\n";
}
