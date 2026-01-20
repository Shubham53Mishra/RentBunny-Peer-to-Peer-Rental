#!/bin/bash

# OTP Login API Test Script
# This script tests all endpoints of the otp_login.php API

BASE_URL="http://localhost/Rent/src/otp_login.php"
MOBILE="9876543210"  # Change this to test with different mobile number
TEST_OTP="123456"    # OTP to test verification

echo "================================"
echo "OTP Login API - Test Suite"
echo "================================"
echo ""

# Test 1: Send OTP
echo "TEST 1: Send OTP"
echo "==============="
echo "Sending OTP to mobile: $MOBILE"
echo ""

SEND_OTP_RESPONSE=$(curl -s -b cookies.txt -c cookies.txt -X POST "$BASE_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=send_otp&mob=$MOBILE")

echo "Response:"
echo "$SEND_OTP_RESPONSE" | jq '.' 2>/dev/null || echo "$SEND_OTP_RESPONSE"
echo ""

# Extract OTP from response (for testing)
ACTUAL_OTP=$(echo "$SEND_OTP_RESPONSE" | jq -r '.otp // empty' 2>/dev/null)

if [ -z "$ACTUAL_OTP" ]; then
    ACTUAL_OTP=$TEST_OTP
    echo "Note: Using test OTP: $TEST_OTP"
else
    echo "Note: Extracted OTP from response: $ACTUAL_OTP"
fi
echo ""

# Test 2: Verify OTP
echo "TEST 2: Verify OTP"
echo "=================="
echo "Verifying OTP: $ACTUAL_OTP for mobile: $MOBILE"
echo ""

VERIFY_OTP_RESPONSE=$(curl -s -b cookies.txt -c cookies.txt -X POST "$BASE_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=verify_otp&mob=$MOBILE&otp=$ACTUAL_OTP")

echo "Response:"
echo "$VERIFY_OTP_RESPONSE" | jq '.' 2>/dev/null || echo "$VERIFY_OTP_RESPONSE"
echo ""

# Test 3: Register Mobile (after OTP verification)
echo "TEST 3: Register with Mobile"
echo "============================="
echo "Registering new user with mobile: $MOBILE"
echo ""

REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL" \
  -b cookies.txt \
  -c cookies.txt \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=register_mobile&name=Test User&email=test@example.com&mob=$MOBILE")

echo "Response:"
echo "$REGISTER_RESPONSE" | jq '.' 2>/dev/null || echo "$REGISTER_RESPONSE"
echo ""

echo "================================"
echo "Test Complete"
echo "================================"
