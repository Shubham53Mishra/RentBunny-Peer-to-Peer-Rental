<?php
/**
 * PaymentHelper Class
 * Use this class to handle payment operations in your product pages
 */

require_once(__DIR__ . '/../../config/razorpay.php');

class PaymentHelper {
    
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Generate payment link for product rental
     */
    public function generatePaymentLink($user_id, $product_id, $rental_cost, $rental_days, $description = '') {
        $params = array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'amount' => intval($rental_cost), // in paise
            'rental_period' => $rental_days,
            'description' => $description ?: 'Rental Payment'
        );
        
        return '/Rent/public/payment.html?' . http_build_query($params);
    }
    
    /**
     * Get payment history for user
     */
    public function getUserPayments($user_id, $limit = 10) {
        $user_id = intval($user_id);
        
        $result = $this->conn->query("
            SELECT p.*, r.start_date, r.end_date, r.status as rental_status
            FROM payments p
            LEFT JOIN rentals r ON p.booking_id = r.id
            WHERE p.user_id = $user_id
            ORDER BY p.created_at DESC
            LIMIT $limit
        ");
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get payment by order ID
     */
    public function getPaymentByOrderId($order_id) {
        $order_id = $this->conn->real_escape_string($order_id);
        
        $result = $this->conn->query("
            SELECT * FROM payments WHERE order_id = '$order_id'
        ");
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get payment by payment ID
     */
    public function getPaymentByPaymentId($payment_id) {
        $payment_id = $this->conn->real_escape_string($payment_id);
        
        $result = $this->conn->query("
            SELECT * FROM payments WHERE payment_id = '$payment_id'
        ");
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Calculate rental cost with tax
     */
    public function calculateRentalCost($daily_rate, $rental_days, $security_deposit = 0, $tax_percent = 18) {
        $base_cost = $daily_rate * $rental_days;
        $tax_amount = round(($base_cost * $tax_percent) / 100);
        $total_cost = $base_cost + $tax_amount + $security_deposit;
        
        return array(
            'base_cost' => $base_cost,
            'tax_amount' => $tax_amount,
            'security_deposit' => $security_deposit,
            'total_cost' => $total_cost,
            'breakdown' => array(
                'daily_rate' => $daily_rate,
                'rental_days' => $rental_days,
                'subtotal' => $base_cost,
                'tax' => $tax_amount . ' (' . $tax_percent . '%)',
                'deposit' => $security_deposit,
                'total' => $total_cost
            )
        );
    }
    
    /**
     * Create rental and payment record
     */
    public function createRentals($user_id, $product_id, $start_date, $end_date, $total_cost, $deposit = 0) {
        $user_id = intval($user_id);
        $product_id = intval($product_id);
        $total_cost = intval($total_cost);
        $deposit = intval($deposit);
        
        $start_date = $this->conn->real_escape_string($start_date);
        $end_date = $this->conn->real_escape_string($end_date);
        
        $stmt = $this->conn->prepare("
            INSERT INTO rentals (user_id, product_id, start_date, end_date, total_cost, deposit_amount, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->bind_param("iissii", $user_id, $product_id, $start_date, $end_date, $total_cost, $deposit);
        
        if($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get rental details
     */
    public function getRentalDetails($rental_id) {
        $rental_id = intval($rental_id);
        
        $result = $this->conn->query("
            SELECT r.*, p.* 
            FROM rentals r
            LEFT JOIN payments p ON r.payment_id = p.id
            WHERE r.id = $rental_id
        ");
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Format amount for display
     */
    public function formatAmount($amount_in_paise) {
        return '₹' . number_format($amount_in_paise / 100, 2);
    }
    
    /**
     * Format currency
     */
    public function formatCurrency($amount, $currency = 'INR') {
        if($currency === 'INR') {
            return '₹' . number_format($amount / 100, 2);
        }
        return number_format($amount / 100, 2);
    }
    
    /**
     * Get payment statistics
     */
    public function getPaymentStats($user_id) {
        $user_id = intval($user_id);
        
        $stats = array();
        
        // Total payments
        $result = $this->conn->query("
            SELECT COUNT(*) as total, SUM(amount) as sum_amount
            FROM payments
            WHERE user_id = $user_id AND status = 'completed'
        ");
        
        $row = $result->fetch_assoc();
        $stats['total_payments'] = $row['total'];
        $stats['total_amount'] = $row['sum_amount'] ?? 0;
        
        // Failed payments
        $result = $this->conn->query("
            SELECT COUNT(*) as failed_count
            FROM payments
            WHERE user_id = $user_id AND status = 'failed'
        ");
        
        $row = $result->fetch_assoc();
        $stats['failed_payments'] = $row['failed_count'];
        
        return $stats;
    }
    
    /**
     * Refund payment
     */
    public function refundPayment($payment_id, $refund_amount = null) {
        $payment_id = intval($payment_id);
        
        // Get payment details
        $result = $this->conn->query("SELECT * FROM payments WHERE id = $payment_id");
        
        if($result->num_rows == 0) {
            return array('success' => false, 'message' => 'Payment not found');
        }
        
        $payment = $result->fetch_assoc();
        
        // Use refund amount or full amount
        $refund_amt = $refund_amount ?? $payment['amount'];
        
        // Update payment status
        $stmt = $this->conn->prepare("
            UPDATE payments 
            SET status = 'refunded', updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("i", $payment_id);
        
        if($stmt->execute()) {
            return array(
                'success' => true,
                'message' => 'Refund initiated',
                'amount' => $refund_amt
            );
        }
        
        return array('success' => false, 'message' => 'Refund failed');
    }
}

?>
