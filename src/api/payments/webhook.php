<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function logError($message) {
    $log_dir = __DIR__ . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_dir . '/webhook.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

try {
    // Database connection
    require_once('../../config/database.php');
    require_once('../../config/razorpay.php');

    if(!isset($conn) || $conn->connect_error) {
        logError("Database connection failed");
        http_response_code(500);
        echo json_encode(['status' => 'error']);
        exit;
    }

    // Get webhook data
    $webhook_body = file_get_contents('php://input');
    $webhook_signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

    if(!defined('RAZORPAY_KEY_SECRET') || empty(RAZORPAY_KEY_SECRET)) {
        logError("Razorpay secret key not configured");
        http_response_code(500);
        echo json_encode(['status' => 'error']);
        exit;
    }

    $key_secret = RAZORPAY_KEY_SECRET;

    // Verify webhook signature
    $verified_signature = hash_hmac('sha256', $webhook_body, $key_secret);

    if($verified_signature !== $webhook_signature) {
        logError("Signature verification failed. Expected: $webhook_signature, Got: $verified_signature");
        http_response_code(400);
        echo json_encode(['status' => 'Signature verification failed']);
        exit;
    }

    // Parse webhook data
    $webhook_data = json_decode($webhook_body, true);
    $event = $webhook_data['event'] ?? '';
    $payload = $webhook_data['payload'] ?? array();

    logError("Event received: $event");

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
                logError("Unknown event: $event");
        }

        http_response_code(200);
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        logError("Handler error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error']);
    }

} catch (Exception $e) {
    logError("Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}

function handle_payment_authorized($payload, $conn) {
    $payment_data = $payload['payment'] ?? array();
    $payment_id = $payment_data['id'] ?? '';
    $order_id = $payment_data['order_id'] ?? '';
    
    if(!$payment_id || !$order_id) return;
    
    $stmt = $conn->prepare("UPDATE payments SET payment_id = ?, status = 'authorized', updated_at = NOW() WHERE order_id = ?");
    if($stmt) {
        $stmt->bind_param("ss", $payment_id, $order_id);
        $stmt->execute();
        $stmt->close();
        logError("Payment authorized: $payment_id");
    }
}

function handle_payment_failed($payload, $conn) {
    $payment_data = $payload['payment'] ?? array();
    $order_id = $payment_data['order_id'] ?? '';
    $error_msg = $payment_data['error_description'] ?? 'Payment failed';
    
    if(!$order_id) return;
    
    $stmt = $conn->prepare("UPDATE payments SET status = 'failed', description = ?, updated_at = NOW() WHERE order_id = ?");
    if($stmt) {
        $stmt->bind_param("ss", $error_msg, $order_id);
        $stmt->execute();
        $stmt->close();
        logError("Payment failed: $order_id - $error_msg");
    }
}

function handle_payment_captured($payload, $conn) {
    $payment_data = $payload['payment'] ?? array();
    $payment_id = $payment_data['id'] ?? '';
    
    if(!$payment_id) return;
    
    $stmt = $conn->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE payment_id = ?");
    if($stmt) {
        $stmt->bind_param("s", $payment_id);
        $stmt->execute();
        $stmt->close();
        logError("Payment captured: $payment_id");
    }
}

function handle_order_paid($payload, $conn) {
    $order_data = $payload['order'] ?? array();
    $order_id = $order_data['id'] ?? '';
    
    if(!$order_id) return;
    
    $stmt = $conn->prepare("UPDATE payments SET status = 'completed', updated_at = NOW() WHERE order_id = ?");
    if($stmt) {
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $stmt->close();
        logError("Order paid: $order_id");
    }
}
?>

