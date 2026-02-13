<?php
// Razorpay Configuration

// Production Keys
define('RAZORPAY_KEY_ID', 'rzp_live_wwpGzdI2GDWS29');
define('RAZORPAY_KEY_SECRET', 'II29D8pGMGLBYWGZn8qgo2Pq');

// Razorpay API Endpoints
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1');

// Callback URLs
define('RAZORPAY_CALLBACK_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/api/payments/verify_payment.php');
define('RAZORPAY_WEBHOOK_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/api/payments/webhook.php');

?>
