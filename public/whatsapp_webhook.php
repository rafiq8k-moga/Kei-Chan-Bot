<?php

require_once __DIR__ . '/../config/credentials.php';
$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../agents/ChatAgent.php';
require_once __DIR__ . '/../utils/Logger.php';

$logger = new Logger($config['paths']['logs']);

// Only POST method is allowed for Fonnte Webhook
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Get the POST body
header('Content-Type: application/json; charset=utf-8');
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

$logger->info("WhatsApp Fonnte Update", ['sender' => $data['sender'] ?? 'unknown']);

// Fonnte Webhook Data
$device = $data['device'] ?? '';
$sender = $data['sender'] ?? '';
$message = $data['message'] ?? '';
$name = $data['name'] ?? '';

// Ignore empty messages or messages not from a sender
if (empty($message) || empty($sender)) {
    echo json_encode(['status' => true, 'message' => 'ignored']);
    exit;
}

// Init ChatAgent with whatsapp platform
$chatAgent = new ChatAgent($config, 'whatsapp');

$normalizedText = strtolower(trim($message));
$commandAliases = [
    'imgsfw',
    'imagesfw',
    'imgnsfw',
    'imagensfw',
];

// Check for commands
// Fonnte doesn't have slash commands, but users might type /command or !command
if (strpos($message, '/') === 0 || strpos($message, '!') === 0) {
    $parts = explode(' ', $message);
    $command = ltrim($parts[0], '!'); // Normalize command by removing ! if present
    
    // Convert text like !start or /start to /start for ChatAgent
    if (strpos($command, '/') !== 0) {
        $command = '/' . $command;
    }
    
    $chatAgent->handleCommand($sender, $command);
} elseif (in_array($normalizedText, $commandAliases, true)) {
    $chatAgent->handleCommand($sender, '/' . $normalizedText);
} else {
    // Regular chat
    $chatAgent->handleMessage($sender, $message);
}

// Fonnte expects JSON response. We can reply directly via webhook response,
// or we let ChatAgent reply via API asynchronously. Fonnte webhook supports
// returning direct reply as JSON. However, Groq API call takes time.
// Since ChatAgent currently uses synchronous API call to Groq and then API call to telegram,
// we will let ChatAgent handle the sending via WhatsAppAPI and just return status ok here.
echo json_encode(['status' => true]);
