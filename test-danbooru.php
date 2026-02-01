<?php
// Quick test script for Danbooru API integration

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/services/ImageService.php';

$config = require __DIR__ . '/config/config.php';
$imageService = new ImageService($config);

echo "=== Testing Danbooru API Integration ===\n\n";
echo "Fetching random Kei image...\n";

$imageUrl = $imageService->getRandomImage();

if ($imageUrl) {
    echo "✓ Success!\n";
    echo "Image URL: {$imageUrl}\n";
} else {
    echo "✗ Failed to fetch image\n";
}

echo "\n=== Test completed ===\n";
