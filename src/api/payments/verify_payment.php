<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

function logError($message) {
    $log_dir = __DIR__ . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_dir . '/verify_error.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

try {
    // Database connection
    require_once('../common/db.php');
    require_once('../common/razorpay.php');

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
        throw new Exception('Missing payment verification data (payment_id, order_id, or signature)');
    }

    $payment_id = trim($data['payment_id']);
    $order_id = trim($data['order_id']);
    $signature = trim($data['signature']);

    logError("Verification attempt - Order: $order_id, Payment: $payment_id");

    if(!defined('RAZORPAY_KEY_SECRET') || empty(RAZORPAY_KEY_SECRET)) {
        logError("Razorpay secret key not configured");
        throw new Exception('Razorpay secret key not configured');
    }

    $key_secret = RAZORPAY_KEY_SECRET;

    // Verify signature - CORRECT RAZORPAY FORMAT
    $payload_to_verify = $order_id . "|" . $payment_id;
    $verified_signature = hash_hmac('sha256', $payload_to_verify, $key_secret);

    logError("Expected signature: $verified_signature");
    logError("Received signature: $signature");
    logError("Payload verified: $payload_to_verify");

    // Compare signatures (case-sensitive, exact match)
    if($verified_signature !== $signature) {
        logError("Signature mismatch!");
        throw new Exception('Signature verification failed - Signatures do not match');
    }

    // Signature verified successfully
    logError("Signature verified successfully!");

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

    $affected = $stmt->affected_rows;
    $stmt->close();

    logError("Updated $affected rows in payments table");

    // Fetch payment details (without joining users table)
    $payment_query = $conn->query("
        SELECT p.id, p.order_id, p.payment_id, p.amount, p.user_id, p.product_id, p.status 
        FROM payments p
        WHERE p.order_id = '" . $conn->real_escape_string($order_id) . "'
    ");

    if(!$payment_query) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    if($payment_query->num_rows == 0) {
        throw new Exception('Payment not found after update');
    }

    $payment = $payment_query->fetch_assoc();
    logError("Payment fetched - ID: " . $payment['id'] . ", Status: " . $payment['status']);

    // Update rental/booking status if applicable
    if($payment['booking_id'] && $payment['booking_id'] > 0) {
        $stmt = $conn->prepare("
            UPDATE rentals 
            SET payment_id = ?, status = 'confirmed', updated_at = NOW()
            WHERE id = ?
        ");
        
        if($stmt) {
            $payment_id_int = intval($payment['id']);
            $booking_id = intval($payment['booking_id']);
            $stmt->bind_param("ii", $payment_id_int, $booking_id);
            $stmt->execute();
            $stmt->close();
            logError("Updated rental status for booking ID: " . $booking_id);
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
        'product_id' => $payment['product_id'],
        'status' => $payment['status']
    ], JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    $error_msg = $e->getMessage();
    logError("Exception: " . $error_msg);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $error_msg,
        'type' => 'error'
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $t) {
    $error_msg = $t->getMessage();
    logError("Throwable: " . $error_msg);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $error_msg,
        'type' => 'fatal'
    ], JSON_UNESCAPED_SLASHES);
}
?>
