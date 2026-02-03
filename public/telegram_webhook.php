<?php

require_once __DIR__ . '/../config/credentials.php';
$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../agents/ChatAgent.php';
require_once __DIR__ . '/../utils/Logger.php';

$logger = new Logger($config['paths']['logs']);

// Only POST method is allowed for Telegram Webhook
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Get the POST body
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

$logger->info("Telegram Update", ['update_id' => $update['update_id'] ?? 'unknown']);

$chatAgent = new ChatAgent($config);

// Handle Message
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    
    // Ignore updates without text (like stickers, photos for now)
    if (empty($text)) {
        echo 'OK';
        exit;
    }
    
    $normalizedText = strtolower(trim($text));
    $commandAliases = [
        'imgsfw',
        'imagesfw',
        'imgnsfw',
        'imagensfw',
    ];

    // Check for commands
    if (strpos($text, '/') === 0) {
        // Extract command (e.g., "/start param" -> "/start")
        $parts = explode(' ', $text);
        $command = $parts[0];
        $chatAgent->handleCommand($chatId, $command);
    } elseif (in_array($normalizedText, $commandAliases, true)) {
        $chatAgent->handleCommand($chatId, $normalizedText);
    } else {
        // Regular chat
        $chatAgent->handleMessage($chatId, $text);
    }
}

echo 'OK';
