<?php
header('Content-Type: application/json');

$health = array();

// Check PHP version
$health['php_version'] = phpversion();

// Check extensions
$health['extensions'] = array(
    'curl' => extension_loaded('curl') ? '✓ Enabled' : '✗ Disabled',
    'json' => extension_loaded('json') ? '✓ Enabled' : '✗ Disabled',
    'mysqli' => extension_loaded('mysqli') ? '✓ Enabled' : '✗ Disabled'
);

// Check configuration files
$health['config_files'] = array();
$config_paths = array(
    'razorpay' => '../../config/razorpay.php',
    'database' => '../../config/database.php'
);

foreach($config_paths as $name => $path) {
    $full_path = __DIR__ . '/' . $path;
    $health['config_files'][$name] = file_exists($full_path) ? '✓ Found' : '✗ Not Found';
}

// Check Razorpay config
try {
    require_once('../../config/razorpay.php');
    $health['razorpay_config'] = array(
        'key_id' => defined('RAZORPAY_KEY_ID') && !empty(RAZORPAY_KEY_ID) ? '✓ Set' : '✗ Not Set',
        'key_secret' => defined('RAZORPAY_KEY_SECRET') && !empty(RAZORPAY_KEY_SECRET) ? '✓ Set' : '✗ Not Set',
        'api_url' => defined('RAZORPAY_API_URL') ? RAZORPAY_API_URL : '✗ Not Set'
    );
} catch (Exception $e) {
    $health['razorpay_config'] = array('error' => $e->getMessage());
}

// Check Database connection
try {
    require_once('../../config/database.php');
    if(isset($conn)) {
        if($conn->connect_error) {
            $health['database'] = '✗ Connection Error: ' . $conn->connect_error;
        } else {
            // Check if payments table exists
            $result = $conn->query("SHOW TABLES LIKE 'payments'");
            $payments_table = $result && $result->num_rows > 0 ? '✓ Exists' : '✗ Not Found';

            $result = $conn->query("SHOW TABLES LIKE 'rentals'");
            $rentals_table = $result && $result->num_rows > 0 ? '✓ Exists' : '✗ Not Found';

            $health['database'] = array(
                'status' => '✓ Connected',
                'payments_table' => $payments_table,
                'rentals_table' => $rentals_table
            );
        }
    }
} catch (Exception $e) {
    $health['database'] = '✗ Error: ' . $e->getMessage();
}

// Test cURL connectivity to Razorpay
try {
    if(extension_loaded('curl')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if($curl_error) {
            $health['curl_test'] = '✗ Error: ' . $curl_error;
        } else {
            $health['curl_test'] = $http_code > 0 ? '✓ API Reachable' : '✗ Cannot reach Razorpay API';
        }
    }
} catch (Exception $e) {
    $health['curl_test'] = '✗ ' . $e->getMessage();
}

// Check logs directory
$log_dir = __DIR__ . '/logs';
$health['logs_directory'] = is_dir($log_dir) ? (is_writable($log_dir) ? '✓ Writable' : '✗ Read-only') : '✗ Not Found';

// Check recent errors
$error_log = $log_dir . '/error.log';
if(file_exists($error_log)) {
    $errors = array_slice(file($error_log), -5);
    $health['recent_errors'] = count($errors) > 0 ? implode("", $errors) : 'No errors';
}

http_response_code(200);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>