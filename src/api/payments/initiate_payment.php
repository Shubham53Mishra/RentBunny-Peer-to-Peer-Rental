<?php
header('Content-Type: application/json');

// Database connection
require_once('../../config/database.php');
require_once('../../config/razorpay.php');

$response = array();

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(!isset($data['user_id']) || !isset($data['amount']) || !isset($data['product_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $user_id = intval($data['user_id']);
    $amount = intval($data['amount']); // Amount in paise
    $product_id = intval($data['product_id']);
    $description = isset($data['description']) ? $data['description'] : 'Rental Payment';
    $rental_period = isset($data['rental_period']) ? intval($data['rental_period']) : 0;

    // Verify user exists
    $user_check = $conn->query("SELECT id FROM users WHERE id = $user_id");
    if($user_check->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Generate unique order ID
    $order_id = 'ORD_' . $user_id . '_' . time() . '_' . rand(100, 999);

    // Razorpay API credentials
    $key_id = RAZORPAY_KEY_ID;
    $key_secret = RAZORPAY_KEY_SECRET;

    // Prepare Razorpay API request
    $api_url = RAZORPAY_API_URL . '/orders';
    
    $order_data = array(
        'amount' => $amount,
        'currency' => 'INR',
        'receipt' => $order_id,
        'payment_capture' => 1, // Auto-capture payments
        'notes' => array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'description' => $description
        )
    );

    // Make API request to Razorpay
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($order_data));
    curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $api_response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_status != 200) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create Razorpay order']);
        exit;
    }

    $order_response = json_decode($api_response, true);

    if(!isset($order_response['id'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Invalid Razorpay response']);
        exit;
    }

    $razorpay_order_id = $order_response['id'];

    // Store order in database
    $stmt = $conn->prepare("
        INSERT INTO payments (user_id, order_id, amount, currency, status, description, booking_id, product_id, rental_period)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $booking_id = isset($data['booking_id']) ? intval($data['booking_id']) : NULL;
    $stmt->bind_param("isisisiii", $user_id, $razorpay_order_id, $amount, $currency = 'INR', $status = 'created', $description, $booking_id, $product_id, $rental_period);

    if(!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }

    // Send response with Razorpay order details
    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $razorpay_order_id,
        'amount' => $amount,
        'currency' => 'INR',
        'key_id' => $key_id,
        'user_id' => $user_id,
        'description' => $description
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

?>
