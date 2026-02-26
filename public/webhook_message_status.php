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

// Fonnte Message Status Webhook Data
$device = $data['device'] ?? '';
$id = $data['id'] ?? '';
$stateid = $data['stateid'] ?? '';
$status = $data['status'] ?? '';
$state = $data['state'] ?? '';

// Log message status update
$logger->info("WhatsApp Message Status Update", [
    'device' => $device,
    'message_id' => $id,
    'state_id' => $stateid,
    'status' => $status,
    'state' => $state
]);

// Database connection example (uncomment if you have database)
/*
$conn = mysqli_connect("localhost", "username", "password", "database");
if (mysqli_connect_errno()) {
    $logger->error("Failed to connect to MySQL: " . mysqli_connect_error());
    echo json_encode(['status' => false, 'error' => 'Database connection failed']);
    exit;
}

// Update status and state in database
if (isset($id) && isset($stateid)) {
    mysqli_query($conn, "UPDATE report SET status = '$status', state = '$state', stateid = '$stateid' WHERE id = '$id'");
} elseif (isset($id) && !isset($stateid)) {
    mysqli_query($conn, "UPDATE report SET status = '$status' WHERE id = '$id'");
} else {
    mysqli_query($conn, "UPDATE report SET state = '$state' WHERE stateid = '$stateid'");
}

mysqli_close($conn);
*/

// You can add custom logic based on message status
switch ($status) {
    case 'pending':
        $logger->info("Message pending", ['message_id' => $id]);
        break;
        
    case 'sent':
        $logger->info("Message sent", ['message_id' => $id]);
        break;
        
    case 'delivered':
        $logger->info("Message delivered", ['message_id' => $id]);
        break;
        
    case 'read':
        $logger->info("Message read", ['message_id' => $id]);
        break;
        
    case 'failed':
        $logger->error("Message failed", ['message_id' => $id, 'state' => $state]);
        break;
        
    default:
        $logger->info("Unknown message status", ['message_id' => $id, 'status' => $status]);
}

// Return success response
echo json_encode(['status' => true, 'message' => 'Message status logged']);
