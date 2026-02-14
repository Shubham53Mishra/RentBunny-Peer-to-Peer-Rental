<?php
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function logError($message) {
    $log_dir = __DIR__ . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_dir . '/verify_error.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

try {
    // Database connection
    require_once('../../config/database.php');
    require_once('../../config/razorpay.php');

    if(!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Get POST data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if(!$data) {
        throw new Exception('Invalid JSON input');
    }

    if(!isset($data['payment_id']) || !isset($data['order_id']) || !isset($data['signature'])) {
        throw new Exception('Missing payment verification data');
    }

    $payment_id = $data['payment_id'];
    $order_id = $data['order_id'];
    $signature = $data['signature'];

    if(!defined('RAZORPAY_KEY_SECRET') || empty(RAZORPAY_KEY_SECRET)) {
        throw new Exception('Razorpay secret key not configured');
    }

    $key_secret = RAZORPAY_KEY_SECRET;

    // Verify signature
    $verified_signature = hash_hmac('sha256', $order_id . "|" . $payment_id, $key_secret);

    if($verified_signature !== $signature) {
        throw new Exception('Signature verification failed');
    }

    // Update payment in database
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_id = ?, status = 'completed', updated_at = NOW()
        WHERE order_id = ?
    ");

    if(!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }

    if(!$stmt->bind_param("ss", $payment_id, $order_id)) {
        throw new Exception('Bind param failed: ' . $stmt->error);
    }

    if(!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();

    // Fetch payment details
    $payment_query = $conn->query("
        SELECT p.*, u.email, u.name 
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.order_id = '" . $conn->real_escape_string($order_id) . "'
    ");

    if(!$payment_query) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    if($payment_query->num_rows == 0) {
        throw new Exception('Payment not found');
    }

    $payment = $payment_query->fetch_assoc();

    // Update rental/booking status if applicable
    if($payment['booking_id'] && $payment['booking_id'] > 0) {
        $stmt = $conn->prepare("
            UPDATE rentals 
            SET payment_id = ?, status = 'confirmed'
            WHERE id = ?
        ");
        
        if($stmt) {
            $payment_id_int = intval($payment['id']);
            $booking_id = intval($payment['booking_id']);
            $stmt->bind_param("ii", $payment_id_int, $booking_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    http_response_code(200);
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
