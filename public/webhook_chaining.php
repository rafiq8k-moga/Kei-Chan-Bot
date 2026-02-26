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

// Log incoming webhook data for chaining
$logger->info("WhatsApp Webhook Chaining", [
    'data' => $data,
    'timestamp' => date('Y-m-d H:i:s')
]);

// Example: Forward data to other systems
// You can modify this based on your integration needs

function forwardToExternalSystem($url, $data) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'User-Agent: Kei-Bot-Webhook-Chain'
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Example integrations (uncomment and modify as needed)
/*
// Forward to CRM system
$crmResponse = forwardToExternalSystem('https://your-crm.com/webhook', $data);
$logger->info("CRM Forward Result", $crmResponse);

// Forward to analytics system
$analyticsResponse = forwardToExternalSystem('https://your-analytics.com/track', $data);
$logger->info("Analytics Forward Result", $analyticsResponse);

// Forward to notification system
$notificationResponse = forwardToExternalSystem('https://your-notifications.com/webhook', $data);
$logger->info("Notification Forward Result", $notificationResponse);
*/

// Example: Save to file for backup
$backupDir = __DIR__ . '/../logs/webhook_chaining_backup';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$backupFile = $backupDir . '/webhook_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($backupFile, json_encode($data, JSON_PRETTY_PRINT));

// Example: Trigger other webhooks based on message content
$message = $data['message'] ?? '';
$sender = $data['sender'] ?? '';

if (stripos($message, 'urgent') !== false) {
    $logger->warning("Urgent message detected", [
        'sender' => $sender,
        'message' => $message,
        'action' => 'high_priority_alert'
    ]);
    
    // You could send SMS, email, or push notification here
    // $urgentResponse = forwardToExternalSystem('https://your-alert-system.com/urgent', $data);
}

if (stripos($message, 'support') !== false) {
    $logger->info("Support request detected", [
        'sender' => $sender,
        'message' => $message,
        'action' => 'create_support_ticket'
    ]);
    
    // You could create support ticket in your system
    // $supportResponse = forwardToExternalSystem('https://your-support-system.com/tickets', $data);
}

// Return success response
echo json_encode([
    'status' => true, 
    'message' => 'Webhook chaining processed',
    'timestamp' => date('Y-m-d H:i:s')
]);
