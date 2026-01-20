<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Login - Rent</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .otp-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        .otp-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .otp-header h2 {
            color: #333;
            font-weight: bold;
        }
        .step {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #eee;
            border-radius: 8px;
            display: none;
        }
        .step.active {
            display: block;
        }
        .step-title {
            font-size: 14px;
            color: #999;
            margin-bottom: 15px;
        }
        .form-control {
            height: 45px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-otp {
            height: 45px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 15px;
        }
        .alert {
            margin-top: 15px;
        }
        .otp-timer {
            text-align: center;
            color: #999;
            font-size: 14px;
            margin-top: 10px;
        }
        .success-message {
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-header">
            <h2>🔐 OTP Login</h2>
            <p class="text-muted">Secure login with one-time password</p>
        </div>

        <!-- Step 1: Mobile Number -->
        <div class="step active" id="step1">
            <div class="step-title">STEP 1: Enter Mobile Number</div>
            <input type="text" class="form-control" id="mobile" placeholder="10-digit mobile number" maxlength="10">
            <button class="btn btn-primary btn-otp w-100" onclick="sendOTP()">Send OTP</button>
            <div id="message1"></div>
        </div>

        <!-- Step 2: OTP Verification -->
        <div class="step" id="step2">
            <div class="step-title">STEP 2: Enter OTP</div>
            <p class="text-muted small">OTP sent to <strong id="mobileDisplay"></strong></p>
            <input type="text" class="form-control" id="otp" placeholder="6-digit OTP" maxlength="6">
            <button class="btn btn-success btn-otp w-100" onclick="verifyOTP()">Verify OTP</button>
            <button class="btn btn-outline-secondary btn-otp w-100" onclick="resendOTP()">Resend OTP</button>
            <div class="otp-timer">OTP valid for <span id="timer">600</span> seconds</div>
            <div id="message2"></div>
        </div>

        <!-- Step 3: Registration (if new user) -->
        <div class="step" id="step3">
            <div class="step-title">STEP 3: Complete Registration</div>
            <input type="text" class="form-control mb-3" id="name" placeholder="Full Name">
            <input type="email" class="form-control mb-3" id="email" placeholder="Email Address">
            <button class="btn btn-success btn-otp w-100" onclick="registerWithMobile()">Register</button>
            <div id="message3"></div>
        </div>

        <!-- Success Message -->
        <div class="step" id="stepSuccess">
            <div class="success-message">
                <h4 class="text-success mb-3">✅ Login Successful!</h4>
                <p id="successMessage"></p>
                <button class="btn btn-primary w-100" onclick="location.reload()">Back to Login</button>
            </div>
        </div>
    </div>

    <script>
        let timerInterval;

        // Step 1: Send OTP
        function sendOTP() {
            const mobile = document.getElementById('mobile').value;
            const messageDiv = document.getElementById('message1');

            if (!mobile || mobile.length !== 10) {
                messageDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid 10-digit mobile number</div>';
                return;
            }

            messageDiv.innerHTML = '<div class="alert alert-info">Sending OTP...</div>';

            fetch('otp_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=send_otp&mob=' + mobile
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    document.getElementById('mobileDisplay').textContent = mobile;
                    document.getElementById('step1').classList.remove('active');
                    document.getElementById('step2').classList.add('active');
                    startTimer();
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }

        // Step 2: Verify OTP
        function verifyOTP() {
            const mobile = document.getElementById('mobile').value;
            const otp = document.getElementById('otp').value;
            const messageDiv = document.getElementById('message2');

            if (!otp || otp.length !== 6) {
                messageDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid 6-digit OTP</div>';
                return;
            }

            messageDiv.innerHTML = '<div class="alert alert-info">Verifying OTP...</div>';

            fetch('otp_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=verify_otp&otp=' + otp + '&mob=' + mobile
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    if (data.data.action === 'register') {
                        // New user - show registration form
                        messageDiv.innerHTML = '';
                        document.getElementById('step2').classList.remove('active');
                        document.getElementById('step3').classList.add('active');
                    } else {
                        // Existing user - login successful
                        clearInterval(timerInterval);
                        document.getElementById('step2').classList.remove('active');
                        document.getElementById('stepSuccess').classList.add('active');
                        document.getElementById('successMessage').textContent = 'Welcome, ' + data.data.name + '! Your login is confirmed.';
                    }
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }

        // Resend OTP
        function resendOTP() {
            const mobile = document.getElementById('mobile').value;
            sendOTP();
        }

        // Step 3: Register new user
        function registerWithMobile() {
            const mobile = document.getElementById('mobile').value;
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const messageDiv = document.getElementById('message3');

            if (!name || !email) {
                messageDiv.innerHTML = '<div class="alert alert-danger">Please fill in all fields</div>';
                return;
            }

            messageDiv.innerHTML = '<div class="alert alert-info">Registering...</div>';

            fetch('otp_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=register_mobile&name=' + encodeURIComponent(name) + '&email=' + encodeURIComponent(email) + '&mob=' + mobile
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    clearInterval(timerInterval);
                    document.getElementById('step3').classList.remove('active');
                    document.getElementById('stepSuccess').classList.add('active');
                    document.getElementById('successMessage').textContent = 'Welcome, ' + data.data.name + '! Your registration and login are complete.';
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }

        // Timer for OTP validity
        function startTimer() {
            let seconds = 600;
            timerInterval = setInterval(() => {
                seconds--;
                document.getElementById('timer').textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('message2').innerHTML = '<div class="alert alert-danger">OTP expired. Please request a new one.</div>';
                    document.getElementById('step2').classList.remove('active');
                    document.getElementById('step1').classList.add('active');
                    document.getElementById('otp').value = '';
                }
            }, 1000);
        }
    </script>
</body>
</html>
