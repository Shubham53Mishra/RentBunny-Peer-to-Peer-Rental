<?php
/**
 * Example: How to integrate payments in your product pages
 * 
 * Copy this code into your product page (post_bike_add.php, etc.)
 */

// At the top of your product posting page, add this:

require_once('../../api/payments/PaymentHelper.php');
require_once('../../config/database.php');

// Initialize payment helper
$paymentHelper = new PaymentHelper($conn);

// Example: User wants to rent a product for 7 days
$product_id = $_POST['product_id'] ?? 0;
$user_id = $_POST['user_id'] ?? $_SESSION['user_id'] ?? 0;
$daily_rental_rate = 500; // ₹5 per day (in paise)
$rental_days = $_POST['rental_days'] ?? 7;
$security_deposit = 5000; // ₹50 security deposit (in paise)

// Calculate total cost
$costCalculation = $paymentHelper->calculateRentalCost(
    $daily_rental_rate,
    $rental_days,
    $security_deposit,
    18 // 18% tax
);

// Generate payment link
$paymentLink = $paymentHelper->generatePaymentLink(
    $user_id,
    $product_id,
    $costCalculation['total_cost'],
    $rental_days,
    'Bicycle Rental for ' . $rental_days . ' days'
);

// Display payment button in your form:
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rent Product</title>
    <style>
        .cost-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .cost-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .cost-total {
            font-weight: bold;
            font-size: 18px;
            color: #667eea;
            padding-top: 10px;
            margin-top: 10px;
        }
        .payment-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .payment-button:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>

<form method="POST">
    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    
    <label>Rental Days:</label>
    <input type="number" name="rental_days" value="7" min="1" max="365">
    
    <!-- Cost Breakdown -->
    <div class="cost-breakdown">
        <h3>Cost Breakdown</h3>
        <div class="cost-item">
            <span>Daily Rate (₹5/day):</span>
            <span><?php echo $paymentHelper->formatAmount($daily_rental_rate * $rental_days); ?></span>
        </div>
        <div class="cost-item">
            <span>Tax (18%):</span>
            <span><?php echo $paymentHelper->formatAmount($costCalculation['tax_amount']); ?></span>
        </div>
        <div class="cost-item">
            <span>Security Deposit:</span>
            <span><?php echo $paymentHelper->formatAmount($security_deposit); ?></span>
        </div>
        <div class="cost-item cost-total">
            <span>Total Amount:</span>
            <span><?php echo $paymentHelper->formatAmount($costCalculation['total_cost']); ?></span>
        </div>
    </div>
    
    <!-- Payment Button -->
    <a href="<?php echo $paymentLink; ?>" class="payment-button">
        💳 Pay Now - <?php echo $paymentHelper->formatAmount($costCalculation['total_cost']); ?>
    </a>
</form>

</body>
</html>

<?php
/**
 * 
 * INTEGRATION STEPS:
 * 
 * 1. Add payment helper at the top of your product page:
 *    require_once('../../../api/payments/PaymentHelper.php');
 *    $paymentHelper = new PaymentHelper($conn);
 * 
 * 2. Calculate rental cost:
 *    $cost = $paymentHelper->calculateRentalCost($daily_rate, $days, $deposit);
 * 
 * 3. Generate payment link:
 *    $link = $paymentHelper->generatePaymentLink($user_id, $product_id, $total, $days, $desc);
 * 
 * 4. Add button to your form:
 *    <a href="<?php echo $link; ?>" class="btn btn-primary">Pay Now</a>
 * 
 * 5. After payment success, user is redirected to:
 *    /Rent/public/index.php?payment=success
 * 
 * 6. You can track payment status:
 *    $payment = $paymentHelper->getPaymentByOrderId($order_id);
 *    if($payment['status'] === 'completed') { ... }
 * 
 */
?>
