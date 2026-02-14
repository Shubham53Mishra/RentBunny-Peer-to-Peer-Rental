<?php
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors
function logError($message) {
    $log_dir = __DIR__ . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_dir . '/error.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

try {
    // Database connection
    require_once('../../config/database.php');
    require_once('../../config/razorpay.php');

    // Check if database connection exists
    if(!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown'));
    }

    // Get POST data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if(!$data) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    if(!isset($data['user_id']) || !isset($data['amount']) || !isset($data['product_id'])) {
        throw new Exception('Missing required fields: user_id, amount, product_id');
    }

    $user_id = intval($data['user_id']);
    $amount = intval($data['amount']); // Amount in paise from app
    $amount_rupees = $amount / 100; // Convert to rupees for database storage
    $product_id = intval($data['product_id']);
    $description = isset($data['description']) ? $data['description'] : 'Rental Payment';
    $rental_period = isset($data['rental_period']) ? intval($data['rental_period']) : 0;
    $booking_id = isset($data['booking_id']) ? intval($data['booking_id']) : NULL;

    // Validate amounts
    if($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }

    if($user_id <= 0) {
        throw new Exception('Invalid user_id');
    }

    // Verify user exists
    $user_check = $conn->query("SELECT id FROM users WHERE id = $user_id");
    if(!$user_check) {
        throw new Exception('Database query failed: ' . $conn->error);
    }
    
    if($user_check->num_rows == 0) {
        throw new Exception('User not found');
    }

    // Check if Razorpay credentials are configured
    if(!defined('RAZORPAY_KEY_ID') || !defined('RAZORPAY_KEY_SECRET')) {
        throw new Exception('Razorpay credentials not configured');
    }

    if(empty(RAZORPAY_KEY_SECRET)) {
        throw new Exception('Razorpay secret key is empty');
    }

    // Generate unique order ID
    $order_id = 'ORD_' . $user_id . '_' . time() . '_' . rand(100, 999);

    // Razorpay API credentials
    $key_id = RAZORPAY_KEY_ID;
    $key_secret = RAZORPAY_KEY_SECRET;

    // Prepare Razorpay API request
    $api_url = 'https://api.razorpay.com/v1/orders';
    
    $order_data = array(
        'amount' => $amount,
        'currency' => 'INR',
        'receipt' => $order_id,
        'payment_capture' => 1,
        'notes' => array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'description' => $description
        )
    );

    // Make API request to Razorpay using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($order_data));
    curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $api_response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check for cURL errors
    if($curl_errno) {
        throw new Exception('cURL error: ' . $curl_error);
    }

    if($http_status != 200) {
        logError("Razorpay API error - Status: $http_status, Response: $api_response");
        throw new Exception('Razorpay API error (Status: ' . $http_status . ')');
    }

    $order_response = json_decode($api_response, true);

    if(!$order_response) {
        throw new Exception('Invalid JSON response from Razorpay');
    }

    if(!isset($order_response['id'])) {
        logError("No order ID in response: " . json_encode($order_response));
        throw new Exception('Failed to get order ID from Razorpay');
    }

    $razorpay_order_id = $order_response['id'];

    // Store order in database using prepared statement
    $stmt = $conn->prepare("
        INSERT INTO payments (user_id, order_id, amount, currency, status, description, booking_id, product_id, rental_period, payment_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if(!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }

    $currency = 'INR';
    $status = 'created';
    $payment_method = 'razorpay';

    // Bind parameters with correct types
    if(!$stmt->bind_param("isisissssi", $user_id, $razorpay_order_id, $amount_rupees, $currency, $status, $description, $booking_id, $product_id, $rental_period, $payment_method)) {
        throw new Exception('Bind param failed: ' . $stmt->error);
    }

    if(!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();

    // Send success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $razorpay_order_id,
        'amount_paise' => $amount,
        'amount_rupees' => $amount_rupees,
        'currency' => 'INR',
        'key_id' => $key_id,
        'user_id' => $user_id,
        'description' => $description
    ]);

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

