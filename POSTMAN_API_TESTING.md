# PEAR User API - Postman Testing Guide

## Base URL
```
https://twowheelerrental.in/pear
or
http://localhost/Rent/src/api/user
```

---

## 1) UPDATE PROFILE
**URL:** `https://twowheelerrental.in/pear/update/profile`
**Method:** POST (Multipart/form-data)

### Headers:
```
token: your_token_here
```

### Body (form-data):
```
Key                 | Type      | Value
--------------------|-----------|------------------
email_address       | text      | user@example.com
full_name           | text      | John Doe
address             | text      | 123 Main Street
profile_image       | file      | (select image file)
```

### Response Success (200):
```json
{
    "success": true,
    "message": "Profile updated successfully"
}
```

### Response Error (400/401/500):
```json
{
    "success": false,
    "message": "Error description",
    "errors": ["field error 1", "field error 2"]
}
```

---

## 2) DELETE ACCOUNT
**URL:** `https://twowheelerrental.in/pear/account/delete`
**Method:** POST

### Headers:
```
token: your_token_here
Content-Type: application/json
```

### Body (raw JSON):
```json
{}
```

### Response Success (200):
```json
{
    "success": true,
    "message": "Account deleted successfully"
}
```

### Response Error:
```json
{
    "success": false,
    "message": "Failed to delete account",
    "error": "error details"
}
```

---

## 3) SEND EMAIL OTP
**URL:** `https://twowheelerrental.in/pear/email/otp`
**Method:** POST

### Headers:
```
token: your_token_here
Content-Type: application/json
```

### Body (raw JSON):
```json
{
    "email": "user@example.com"
}
```

### Response Success (200):
```json
{
    "success": true,
    "message": "OTP sent to email successfully"
}
```

### Response Error (400/429):
```json
{
    "success": false,
    "message": "Please wait before requesting another OTP"
}
```

---

## 4) VERIFY EMAIL OTP
**URL:** `https://twowheelerrental.in/pear/verify/email`
**Method:** POST

### Headers:
```
token: your_token_here
Content-Type: application/json
```

### Body (raw JSON):
```json
{
    "email": "user@example.com",
    "otp": "123456"
}
```

### Response Success (200):
```json
{
    "success": true,
    "message": "Email verified successfully"
}
```

### Response Error (400):
```json
{
    "success": false,
    "message": "Invalid or expired OTP"
}
```

---

## 5) VERIFY MOBILE OTP (Delete Flow)
**URL:** `https://twowheelerrental.in/pear/verify/mobile` (or custom)
**Method:** POST

### Headers:
```
token: your_token_here
Content-Type: application/json
```

### Body (raw JSON):
```json
{
    "mobile": "9876543210",
    "otp": "123456"
}
```

### Response Success (200):
```json
{
    "success": true,
    "message": "Mobile OTP verified successfully. Account deletion approved."
}
```

### Response Error (400):
```json
{
    "success": false,
    "message": "Invalid or expired OTP"
}
```

---

## Testing Steps in Postman

### Step 1: Get Token First
- Login via existing login API to get token
- Copy the token value

### Step 2: Create Collection
1. Open Postman
2. Click "New" → "Collection"
3. Name it "PEAR User API"

### Step 3: Add Requests
1. Add new request for each endpoint
2. Set method (POST/GET)
3. Enter URL
4. Add Headers (token)
5. Add Body (JSON or form-data)

### Step 4: Test Each Endpoint
1. Click "Send"
2. Check Status Code (200 = success)
3. Verify Response JSON

### Step 5: Error Testing
Test with:
- Invalid token → 401
- Missing required fields → 400
- Expired OTP → 400
- File too large → 400
- Invalid email format → 400

---

## Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| 401 Unauthorized | Check if token is correct and not expired |
| 400 Bad Request | Verify all required fields are provided |
| 429 Too Many Requests | Wait before requesting OTP again |
| File upload fails | Check file size < 5MB and format (jpg/png/gif) |
| Email not sent | Check if email service is configured |

---

## Token Acquisition Example

First, login to get token:
```
POST /login
Body: {
    "mobile": "9876543210",
    "otp": "123456"
}

Response:
{
    "success": true,
    "token": "abc123xyz456..."
}
```

Then use this token in all subsequent requests.

---

## Notes
- OTP expires in 10 minutes
- Profile image optional (max 5MB)
- Email must be unique (if already registered, will fail)
- Mobile number must be 10 digits
- All timestamps in UTC
