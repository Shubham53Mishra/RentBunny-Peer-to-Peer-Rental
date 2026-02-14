<?php
header('Content-Type: application/json');

// Quick test endpoint for app developers
$test = array();

try {
    // Test 1: Check if database exists
    require_once('../../config/database.php');

    if(!isset($conn)) {
        throw new Exception('No connection object');
    }

    // Test 2: Check payments table
    $result = $conn->query("SELECT 1 FROM payments LIMIT 1");
    if(!$result && $conn->errno !== 0) {
        throw new Exception('Payments table check failed: ' . $conn->error);
    }

    // Test 3: Try a simple SELECT
    $check = $conn->query("SELECT COUNT(*) as count FROM payments");
    if(!$check) {
        throw new Exception('Count query failed: ' . $conn->error);
    }

    $row = $check->fetch_assoc();
    $test['database'] = '✓ OK';
    $test['payment_records'] = intval($row['count']);

    // Test 4: Check if Razorpay config exists
    require_once('../../config/razorpay.php');

    if(!defined('RAZORPAY_KEY_ID')) {
        throw new Exception('RAZORPAY_KEY_ID not defined');
    }

    $test['razorpay_key_id'] = substr(RAZORPAY_KEY_ID, 0, 10) . '...';
    $test['razorpay_secret_key'] = !empty(RAZORPAY_KEY_SECRET) ? '✓ Set' : '✗ Missing';

    // Test 5: Check if logs directory is writable
    $log_dir = __DIR__ . '/logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    if(is_writable($log_dir)) {
        $test['logs_directory'] = '✓ Writable';
    } else {
        $test['logs_directory'] = '⚠ Read-only (some features may not work)';
    }

    $test['status'] = '✓ All systems operational';

    http_response_code(200);
    echo json_encode($test);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => '✗ Error',
        'message' => $e->getMessage()
    ]);
}
?>