<?php
header('Content-Type: application/json');

// Database connection
require_once('../../config/database.php');
require_once('../../config/razorpay.php');

// Razorpay webhook signature verification
$webhook_body = file_get_contents('php://input');
$webhook_signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

$key_secret = RAZORPAY_KEY_SECRET;

// Verify webhook signature
$verified_signature = hash_hmac('sha256', $webhook_body, $key_secret);

if($verified_signature !== $webhook_signature) {
    http_response_code(400);
    echo json_encode(['status' => 'Signature verification failed']);
    exit;
}

// Parse webhook data
$webhook_data = json_decode($webhook_body, true);
$event = $webhook_data['event'] ?? '';
$payload = $webhook_data['payload'] ?? array();

$log_file = '../../logs/webhook.log';
if(!is_dir('../../logs')) {
    mkdir('../../logs', 0755, true);
}

file_put_contents($log_file, date('Y-m-d H:i:s') . " - Event: $event\n", FILE_APPEND);

try {
    switch($event) {
        case 'payment.authorized':
            handle_payment_authorized($payload, $conn);
            break;

        case 'payment.failed':
            handle_payment_failed($payload, $conn);
            break;

        case 'payment.captured':
            handle_payment_captured($payload, $conn);
            break;

        case 'order.paid':
            handle_order_paid($payload, $conn);
            break;

        default:
            file_put_contents($log_file, "Unknown event: $event\n", FILE_APPEND);
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function handle_payment_authorized($payload, $conn) {
    $payment_data = $payload['payment'] ?? array();
    $payment_id = $payment_data['id'] ?? '';
    $order_id = $payment_data['order_id'] ?? '';
    
    $stmt = $conn->prepare("UPDATE payments SET payment_id = ?, status = 'authorized', updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("ss", $payment_id, $order_id);
    $stmt->execute();
}

function handle_payment_failed($payload, $conn) {
    $payment_data = $payload['payment'] ?? array();
    $order_id = $payment_data['order_id'] ?? '';
    $error_msg = $payment_data['error_description'] ?? 'Payment failed';
    
    $stmt = $conn->prepare("UPDATE payments SET status = 'failed', description = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("ss", $error_msg, $order_id);
    $stmt->execute();
}

function handle_payment_captured($payload, $conn) {
    $payment_data = $payload['payment'] ?? array();
    $payment_id = $payment_data['id'] ?? '';
    
    $stmt = $conn->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE payment_id = ?");
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();
}

function handle_order_paid($payload, $conn) {
    $order_data = $payload['order'] ?? array();
    $order_id = $order_data['id'] ?? '';
    
    $stmt = $conn->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
}

?>
