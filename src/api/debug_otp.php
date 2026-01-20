<?php
header('Content-Type: application/json');
require_once('../common/db.php');

// Get all users with OTP
$sql = "SELECT id, mobile, otp, otp_created_at, otp_expires_at, NOW() as current_time FROM users WHERE otp IS NOT NULL ORDER BY otp_created_at DESC LIMIT 10";
$result = $conn->query($sql);

$data = [];
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    $data = ['message' => 'No OTPs found in database'];
}

echo json_encode($data, JSON_PRETTY_PRINT);
$conn->close();
?>
