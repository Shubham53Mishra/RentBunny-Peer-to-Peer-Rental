<?php
header('Content-Type: application/json');

// Database connection
require_once('../../config/database.php');

$response = array();

try {
    // Get GET parameters
    $order_id = $_GET['order_id'] ?? '';
    $user_id = $_GET['user_id'] ?? '';

    if(!$order_id || !$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing order_id or user_id']);
        exit;
    }

    $user_id = intval($user_id);
    $stmt = $conn->prepare("
        SELECT id, order_id, payment_id, amount, status, created_at, updated_at
        FROM payments
        WHERE order_id = ? AND user_id = ?
    ");

    $stmt->bind_param("si", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }

    $payment = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'payment' => array(
            'order_id' => $payment['order_id'],
            'payment_id' => $payment['payment_id'],
            'amount' => $payment['amount'],
            'status' => $payment['status'],
            'created_at' => $payment['created_at'],
            'updated_at' => $payment['updated_at']
        )
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

?>
