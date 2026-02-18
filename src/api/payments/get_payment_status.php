<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function logError($message) {
    $log_dir = __DIR__ . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_dir . '/status_error.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

try {
    // Database connection
    require_once('../../config/database.php');

    if(!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Get GET parameters
    $order_id = $_GET['order_id'] ?? '';
    $user_id = $_GET['user_id'] ?? '';

    if(!$order_id || !$user_id) {
        throw new Exception('Missing order_id or user_id');
    }

    $user_id = intval($user_id);
    $order_id = $conn->real_escape_string($order_id);

    $stmt = $conn->prepare("
        SELECT id, order_id, payment_id, amount, status, created_at, updated_at
        FROM payments
        WHERE order_id = ? AND user_id = ?
    ");

    if(!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    if(!$stmt->bind_param("si", $order_id, $user_id)) {
        throw new Exception('Bind failed: ' . $stmt->error);
    }

    if(!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if($result->num_rows == 0) {
        throw new Exception('Payment not found');
    }

    $payment = $result->fetch_assoc();
    $stmt->close();

    $response_data = [
        'success' => true,
        'payment' => [
            'order_id' => $payment['order_id'],
            'payment_id' => $payment['payment_id'],
            'amount_rupees' => floatval(round($payment['amount'], 2)),
            'amount_paise' => intval($payment['amount'] * 100),
            'status' => $payment['status'],
            'created_at' => $payment['created_at'],
            'updated_at' => $payment['updated_at']
        ]
    ];

    http_response_code(200);
    echo json_encode($response_data, JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    logError("Exception: " . $error_msg);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error_msg,
        'type' => 'error'
    ]);
} catch (Throwable $t) {
    $error_msg = $t->getMessage();
    logError("Throwable: " . $error_msg);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error_msg,
        'type' => 'fatal'
    ]);
}
?>
