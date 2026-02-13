# Razorpay Payment Integration Guide

## Setup Instructions

### 1. Install Razorpay SDK using Composer

Run this command in your project root:

```bash
cd c:\xampp\htdocs\Rent
composer update
```

Or if composer is not installed:

```bash
composer install
```

### 2. Add Your Razorpay API Keys

Edit `config/razorpay.php` and add your Razorpay secret key:

```php
define('RAZORPAY_KEY_SECRET', 'YOUR_SECRET_KEY_HERE');
```

**Current setup:**
- Key ID: `rzp_live_wwpGzdI2GDWS29`
- Secret Key: Add your production secret key

### 3. Create Database Tables

Run the SQL script to create payment and rental tables:

```bash
mysql -u root rentbunny < create_payments_table.sql
```

Or copy and paste the SQL from `create_payments_table.sql` in phpMyAdmin

### 4. Configure Webhook URL in Razorpay Dashboard

1. Go to Razorpay Dashboard → Settings → Webhooks
2. Add webhook endpoint: `http://yourdomain.com/Rent/src/api/payments/webhook.php`
3. Select events:
   - `payment.authorized`
   - `payment.failed`
   - `payment.captured`
   - `order.paid`

---

## API Endpoints

### 1. Initiate Payment
**Endpoint:** `POST /src/api/payments/initiate_payment.php`

**Request:**
```json
{
    "user_id": 1,
    "product_id": 5,
    "amount": 5000,
    "rental_period": 7,
    "description": "Bicycle Rental for 7 days",
    "booking_id": 123
}
```

**Response (Success):**
```json
{
    "success": true,
    "order_id": "ORD_1_1234567890_456",
    "amount": 5000,
    "currency": "INR",
    "key_id": "rzp_live_wwpGzdI2GDWS29",
    "user_id": 1,
    "description": "Bicycle Rental for 7 days"
}
```

### 2. Verify Payment
**Endpoint:** `POST /src/api/payments/verify_payment.php`

**Request:**
```json
{
    "payment_id": "pay_1234567890abcdef",
    "order_id": "ORD_1_1234567890_456",
    "signature": "e7d5a8f9e8c9b8a7f6e5d4c3b2a1f0e9"
}
```

**Response (Success):**
```json
{
    "success": true,
    "payment_id": "pay_1234567890abcdef",
    "order_id": "ORD_1_1234567890_456",
    "amount": 5000,
    "user_id": 1,
    "user_email": "user@example.com"
}
```

### 3. Get Payment Status
**Endpoint:** `GET /src/api/payments/get_payment_status.php?order_id=ORD_1_1234567890_456&user_id=1`

**Response:**
```json
{
    "success": true,
    "payment": {
        "order_id": "ORD_1_1234567890_456",
        "payment_id": "pay_1234567890abcdef",
        "amount": 5000,
        "status": "completed",
        "created_at": "2026-02-13 10:30:00",
        "updated_at": "2026-02-13 10:35:00"
    }
}
```

---

## Frontend Integration

### Payment Page
Access payment page: `public/payment.html`

**URL Parameters:**
- `user_id` - User ID (required)
- `product_id` - Product ID (required)
- `amount` - Amount in paise (required) - 5000 = ₹50
- `rental_period` - Rental period in days (optional)
- `description` - Payment description (optional)

**Example URL:**
```
http://localhost/Rent/public/payment.html?user_id=1&product_id=5&amount=5000&rental_period=7&description=Bicycle Rental
```

### JavaScript Integration

```javascript
// Redirect to payment page
function goToPayment(userId, productId, amountInPaise, rentalDays) {
    const params = new URLSearchParams({
        user_id: userId,
        product_id: productId,
        amount: amountInPaise,
        rental_period: rentalDays,
        description: 'Rental Payment'
    });
    
    window.location.href = `/Rent/public/payment.html?${params}`;
}

// Example: Rent a bicycle for 7 days at ₹50/day = ₹350
goToPayment(1, 5, 35000, 7);
```

---

## Database Schema

### Payments Table
```
id - Payment ID
user_id - User who made payment
order_id - Unique order ID (generated)
payment_id - Razorpay payment ID
amount - Amount in paise
currency - Currency (INR)
status - Payment status (created, authorized, completed, failed, cancelled)
description - Payment description
booking_id - Associated rental/booking ID
product_id - Product being rented
rental_period - Rental duration in days
payment_method - Payment method used
razorpay_signature - Webhook signature
created_at - Timestamp
updated_at - Timestamp
```

### Rentals Table
```
id - Rental ID
user_id - User renting
product_id - Product being rented
start_date - Rental start date
end_date - Rental end date
status - Rental status
total_cost - Total cost in paise
payment_id - Associated payment ID
deposit_amount - Security deposit in paise
deposit_status - Deposit status
created_at - Timestamp
updated_at - Timestamp
```

---

## Payment Flow

1. **User Initiates Payment**
   - Click "Pay Now" button
   - Call `/initiate_payment.php` API

2. **Razorpay Checkout Opens**
   - Razorpay modal/form appears
   - User enters payment details

3. **Payment Processing**
   - Razorpay processes payment
   - Returns payment_id and signature

4. **Verification**
   - Call `/verify_payment.php` API
   - Signature is validated
   - Payment status updated in database

5. **Webhook Notification**
   - Razorpay sends webhook event
   - `/webhook.php` updates payment status
   - Rental status is updated

---

## Testing

### Test Cards
Razorpay provides test cards for testing:

**Successful Payment:**
- Card: 4111 1111 1111 1111
- Expiry: Any future date
- CVV: Any 3 digits

**Failed Payment:**
- Card: 4111 1111 1111 2222
- Expiry: Any future date
- CVV: Any 3 digits

### Test with POST Request (cURL)

```bash
# Initiate payment
curl -X POST http://localhost/Rent/src/api/payments/initiate_payment.php \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "product_id": 5,
    "amount": 5000,
    "rental_period": 7,
    "description": "Test Rental"
  }'

# Verify payment
curl -X POST http://localhost/Rent/src/api/payments/verify_payment.php \
  -H "Content-Type: application/json" \
  -d '{
    "payment_id": "pay_test123",
    "order_id": "ORD_1_1234567890_456",
    "signature": "signature_here"
  }'
```

---

## Security Considerations

1. ✅ All requests validate required fields
2. ✅ Signature verification prevents tampering
3. ✅ Database stores sensitive payment data securely
4. ✅ Webhook validates Razorpay signature
5. ✅ CORS and XSS protections recommended
6. ✅ Use HTTPS in production (required by Razorpay)

---

## Troubleshooting

### Issue: Signature Verification Failed
- Check if your secret key in config/razorpay.php is correct
- Ensure you're using production keys, not test keys

### Issue: Order Creation Failed
- Check if cURL is enabled on server
- Verify API credentials
- Check server logs for errors

### Issue: Payment Data Not Updating
- Ensure database tables are created correctly
- Check database user permissions
- Verify database connection in config/database.php

### Issue: Webhook Not Called
- Configure webhook URL in Razorpay dashboard
- Ensure URL is publicly accessible
- Check webhook logs in `/logs/webhook.log`

---

## Support

For Razorpay documentation: https://razorpay.com/docs/
For integration issues, check: `/logs/webhook.log`

---

**Last Updated:** February 13, 2026
**Razorpay Integration v1.0**
