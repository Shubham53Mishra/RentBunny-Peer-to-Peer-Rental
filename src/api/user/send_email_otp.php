<?php
header('Content-Type: application/json');
require_once('../../common/db.php');

$response = array();

// Get token from header
$token = '';
if(function_exists('getallheaders')) {
    $headers = getallheaders();
    $token = isset($headers['token']) ? $headers['token'] : (isset($headers['Token']) ? $headers['Token'] : '');
}
if(empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
}
if(empty($token) && isset($_SERVER['HTTP_TOKEN'])) {
    $token = $_SERVER['HTTP_TOKEN'];
}
if(empty($token) && isset($_GET['token'])) {
    $token = $_GET['token'];
}

if(empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token not provided']);
    exit;
}

$token = mysqli_real_escape_string($conn, $token);
$sql = "SELECT id FROM users WHERE token = '$token' AND token IS NOT NULL";
$result = $conn->query($sql);
if($result->num_rows == 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$user_row = $result->fetch_assoc();
$user_id = $user_row['id'];

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Validate email
$email = isset($data['email']) ? mysqli_real_escape_string($conn, trim($data['email'])) : '';

if(empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Check if email is already registered by another user
$check_email = "SELECT id FROM users WHERE email_address = '$email' AND id != $user_id";
$check_result = $conn->query($check_email);
if($check_result->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// Generate OTP
$otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Check for recent OTP requests (rate limiting)
$recent_otp = "SELECT id FROM email_otp WHERE user_id = $user_id AND email = '$email' AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
$recent_result = $conn->query($recent_otp);
if($recent_result->num_rows > 0) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Please wait before requesting another OTP']);
    exit;
}

// Store OTP in database
$insert_otp = "INSERT INTO email_otp (user_id, email, otp, created_at) VALUES ($user_id, '$email', '$otp', NOW())";
if($conn->query($insert_otp)) {
    // Send email (implementation depends on your email service)
    // This is a placeholder - implement actual email sending
    $email_sent = sendEmailOTP($email, $otp);
    
    if($email_sent) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent to email successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP', 'error' => $conn->error]);
}

// Function to send email OTP using Gmail SMTP
function sendEmailOTP($email, $otp) {
    // Gmail credentials
    $sender_email = 'shubhammishra2310@gmail.com';
    $app_password = 'tlox wmbk ibam baew'; // Gmail App Password
    
    // Email subject and body
    $subject = "Your Email Verification OTP - RentBunny";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            .otp-box { background-color: #e7f3ff; border: 2px solid #007bff; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 5px; }
            .footer { text-align: center; padding: 15px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>RentBunny Email Verification</h2>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>Your One-Time Password (OTP) for email verification is:</p>
                <div class='otp-box'>$otp</div>
                <p><strong>This OTP will expire in 10 minutes.</strong></p>
                <p>Do not share this OTP with anyone. If you did not request this, please ignore this email.</p>
                <p>Thank you,<br/>RentBunny Team</p>
            </div>
            <div class='footer'>
                <p>&copy; 2026 RentBunny. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Use mail() function with proper headers for HTML email
    $headers = "From: RentBunny <" . $sender_email . ">\r\n";
    $headers .= "Reply-To: " . $sender_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Try using mail() function (requires mail server configured)
    if(mail($email, $subject, $message, $headers)) {
        return true;
    }
    
    // Fallback: Try PHPMailer if available
    if(file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../../../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $sender_email;
            $mail->Password = $app_password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            $mail->setFrom($sender_email, 'RentBunny');
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $message;
            
            if($mail->send()) {
                return true;
            }
        } catch(Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
        }
    }
    
    return false;
}
?>
