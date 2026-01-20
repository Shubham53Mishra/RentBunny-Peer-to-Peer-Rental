# OTP Login System - Complete Setup Guide

## 📁 File Structure

```
rentbunny/
├── config/
│   ├── database.php           ✅ Database configuration
│   └── setup.php              ✅ Auto table setup
├── src/
│   ├── api.php                ✅ Main API
│   ├── otp_login.php          ✅ OTP API
│   ├── api_get.php            ✅ GET endpoints
│   ├── login_process.php      ✅ Form login
│   └── register_process.php   ✅ Form register
├── views/
│   ├── otp_login.php          ✅ OTP Login Form UI
│   ├── login.php              ✅ Email Login Form
│   ├── register.php           ✅ Register Form
│   └── home.php               ✅ Home Page
├── public/
│   └── index.php              ✅ Entry point
├── assets/                    ✅ CSS, JS, Images
├── check_api.php              ✅ API Status Checker
└── SETUP.md                   ✅ This file
```

---

## 🚀 Setup Steps

### Step 1: Database Configuration
Update [config/database.php](config/database.php):

**Local:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'RentBunny');
```

**Server:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'ridobdbg_rentbunny');
define('DB_PASS', 'password');
define('DB_NAME', 'ridobdbg_rentbunny');
```

---

### Step 2: Create Database Tables
Open in browser:
```
http://localhost/Rent/config/setup.php
```

This automatically creates/updates:
- `users` table
- All OTP columns

---

### Step 3: Configure API Key
Update [src/otp_login.php](src/otp_login.php) Line 7:
```php
$apiKey = "your-2factor-api-key";
```

---

## 🎯 How to Use

### Option 1: Web Form (Recommended)
```
http://localhost/Rent/views/otp_login.php
```

---

### Option 2: API with curl

**Send OTP:**
```bash
curl -c cookies.txt -X POST http://localhost/Rent/src/otp_login.php \
  -d "action=send_otp&mob=7562841345"
```

**Verify OTP:**
```bash
curl -b cookies.txt -X POST http://localhost/Rent/src/otp_login.php \
  -d "action=verify_otp&otp=123456&mob=7562841345"
```

**Register:**
```bash
curl -b cookies.txt -X POST http://localhost/Rent/src/otp_login.php \
  -d "action=register_mobile&name=John&email=john@test.com&mob=7562841345"
```

---

## 📋 Database Schema

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(255),
    mobile VARCHAR(15) UNIQUE,
    otp VARCHAR(6),
    otp_created_at TIMESTAMP NULL,
    otp_expires_at TIMESTAMP NULL,
    otp_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📞 Upload to Server

**FTP Upload:**
- Path: `/public_html/rentbunny/`
- Upload: config/, src/, views/, public/, assets/

**After Upload:**
1. Run setup: `https://your-domain/rentbunny/config/setup.php`
2. Check: `https://your-domain/rentbunny/check_api.php`
3. Login: `https://your-domain/rentbunny/views/otp_login.php`

---

## ✅ All Files Ready!

- [config/database.php](config/database.php) ✅
- [src/otp_login.php](src/otp_login.php) ✅
- [views/otp_login.php](views/otp_login.php) ✅
- [check_api.php](check_api.php) ✅
- [config/setup.php](config/setup.php) ✅
- [src/api.php](src/api.php) ✅

---

**You can now upload these files to your server!** 🚀
