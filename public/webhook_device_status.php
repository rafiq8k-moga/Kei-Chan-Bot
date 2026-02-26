<?php

require_once __DIR__ . '/../config/credentials.php';
$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../utils/Logger.php';

$logger = new Logger($config['paths']['logs']);

// Set header for JSON response
header('Content-Type: application/json; charset=utf-8');

// Get the POST body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
    exit;
}

// Fonnte Device Status Webhook Data
$device = $data['device'] ?? '';
$status = $data['status'] ?? '';
$timestamp = $data['timestamp'] ?? '';
$reason = $data['reason'] ?? '';

// Log device status change
$logger->info("WhatsApp Device Status Update", [
    'device' => $device,
    'status' => $status,
    'reason' => $reason,
    'timestamp' => $timestamp
]);

// You can add custom logic here based on device status
switch ($status) {
    case 'connect':
        $logger->info("Device connected", ['device' => $device]);
        // Add your logic when device connects
        // For example: notify admin, update database, etc.
        break;
        
    case 'disconnect':
        $logger->warning("Device disconnected", [
            'device' => $device, 
            'reason' => $reason
        ]);
        // Add your logic when device disconnects
        // For example: send alert, try to reconnect, etc.
        break;
        
    default:
        $logger->info("Unknown device status", [
            'device' => $device, 
            'status' => $status
        ]);
}

// Return success response
echo json_encode(['status' => true, 'message' => 'Device status logged']);
