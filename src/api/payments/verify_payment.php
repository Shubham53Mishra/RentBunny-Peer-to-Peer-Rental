<?php
header('Content-Type: application/json');

// Database connection
require_once('../../config/database.php');
require_once('../../config/razorpay.php');

$response = array();

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!isset($data['payment_id']) || !isset($data['order_id']) || !isset($data['signature'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing payment verification data']);
        exit;
    }

    $payment_id = $data['payment_id'];
    $order_id = $data['order_id'];
    $signature = $data['signature'];

    $key_secret = RAZORPAY_KEY_SECRET;

    // Verify signature
    $verified_signature = hash_hmac('sha256', $order_id . "|" . $payment_id, $key_secret);

    if($verified_signature !== $signature) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Signature verification failed']);
        exit;
    }

    // Update payment in database
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_id = ?, status = 'completed', updated_at = NOW()
        WHERE order_id = ?
    ");

    $stmt->bind_param("ss", $payment_id, $order_id);

    if(!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }

    // Fetch payment details
    $payment_query = $conn->query("
        SELECT p.*, u.email, u.name 
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.order_id = '$order_id'
    ");

    if($payment_query->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }

    $payment = $payment_query->fetch_assoc();

    // Update rental/booking status if applicable
    if($payment['booking_id']) {
        $conn->query("
            UPDATE rentals 
            SET payment_id = " . $payment['id'] . ", status = 'confirmed'
            WHERE id = " . $payment['booking_id']
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'payment_id' => $payment_id,
        'order_id' => $order_id,
        'amount' => $payment['amount'],
        'user_id' => $payment['user_id'],
        'user_email' => $payment['email']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

?>
