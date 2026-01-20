<!DOCTYPE html>
<html>
<head>
    <title>API Status Check</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        h1 { color: #333; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 API Status Check</h1>
        
        <!-- Check 1: Database Connection -->
        <div class="status">
            <h3>1. Database Connection</h3>
            <?php
                require_once('config/database.php');
                
                if($conn->connect_error) {
                    echo '<div class="error">❌ Database Connection Failed: ' . $conn->connect_error . '</div>';
                } else {
                    echo '<div class="success">✅ Database Connected Successfully!</div>';
                    echo '<p>Database: ' . DB_NAME . '</p>';
                    echo '<p>User: ' . DB_USER . '</p>';
                }
            ?>
        </div>

        <!-- Check 2: Users Table -->
        <div class="status">
            <h3>2. Users Table Check</h3>
            <?php
                $check_table = "SHOW TABLES LIKE 'users'";
                $result = $conn->query($check_table);
                
                if($result->num_rows > 0) {
                    echo '<div class="success">✅ Users Table Exists!</div>';
                    
                    // Show columns
                    $show_columns = "SHOW COLUMNS FROM users";
                    $columns_result = $conn->query($show_columns);
                    
                    echo '<h4>Columns:</h4><ul>';
                    while($col = $columns_result->fetch_assoc()) {
                        echo '<li><strong>' . $col['Field'] . '</strong> (' . $col['Type'] . ')</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<div class="error">❌ Users Table NOT Found!</div>';
                }
            ?>
        </div>

        <!-- Check 3: API Endpoint Test -->
        <div class="status">
            <h3>3. OTP API Test</h3>
            <p>Test the OTP login API:</p>
            <form method="POST" style="margin: 10px 0;">
                <input type="text" name="mobile" placeholder="Enter mobile number" required style="padding: 8px; width: 200px;">
                <button type="submit" name="test_api">Test Send OTP</button>
            </form>
            
            <?php
                if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_api'])) {
                    $mobile = $_POST['mobile'];
                    echo '<div class="info"><strong>Testing with Mobile:</strong> ' . htmlspecialchars($mobile) . '</div>';
                    
                    // Simulate API call
                    $otp = rand(100000, 999999);
                    $expires_at = date('Y-m-d H:i:s', time() + 600);
                    
                    $check_sql = "SELECT id FROM users WHERE mobile = '$mobile'";
                    $check_result = $conn->query($check_sql);
                    
                    if($check_result->num_rows > 0) {
                        $user = $check_result->fetch_assoc();
                        $user_id = $user['id'];
                        $update_sql = "UPDATE users SET otp = '$otp', otp_created_at = NOW(), otp_expires_at = '$expires_at', otp_verified = FALSE WHERE id = $user_id";
                        
                        if($conn->query($update_sql)) {
                            echo '<div class="success">✅ OTP Updated in Database!<br>';
                            echo 'Mobile: ' . htmlspecialchars($mobile) . '<br>';
                            echo 'OTP: <strong>' . $otp . '</strong><br>';
                            echo 'Expires: ' . $expires_at . '</div>';
                        } else {
                            echo '<div class="error">❌ Error: ' . $conn->error . '</div>';
                        }
                    } else {
                        $insert_sql = "INSERT INTO users (mobile, otp, otp_created_at, otp_expires_at, otp_verified, name, email, password) 
                                      VALUES ('$mobile', '$otp', NOW(), '$expires_at', FALSE, 'Pending', 'pending@example.com', '')";
                        
                        if($conn->query($insert_sql)) {
                            echo '<div class="success">✅ New User Created with OTP!<br>';
                            echo 'Mobile: ' . htmlspecialchars($mobile) . '<br>';
                            echo 'OTP: <strong>' . $otp . '</strong><br>';
                            echo 'Expires: ' . $expires_at . '</div>';
                        } else {
                            echo '<div class="error">❌ Error: ' . $conn->error . '</div>';
                        }
                    }
                }
            ?>
        </div>

        <!-- Check 4: File Structure -->
        <div class="status">
            <h3>4. File Structure Check</h3>
            <?php
                $required_files = array(
                    'config/database.php',
                    'src/otp_login.php',
                    'views/otp_login.php',
                    'public/index.php'
                );
                
                foreach($required_files as $file) {
                    if(file_exists($file)) {
                        echo '<div class="success">✅ ' . $file . '</div>';
                    } else {
                        echo '<div class="error">❌ ' . $file . ' NOT FOUND</div>';
                    }
                }
            ?>
        </div>

        <!-- Check 5: PHP Info -->
        <div class="status">
            <h3>5. PHP Configuration</h3>
            <ul>
                <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                <li><strong>MySQL Support:</strong> <?php echo extension_loaded('mysqli') ? '✅ Yes' : '❌ No'; ?></li>
                <li><strong>cURL Support:</strong> <?php echo extension_loaded('curl') ? '✅ Yes' : '❌ No'; ?></li>
            </ul>
        </div>

        <hr>
        <p style="color: #666; font-size: 12px;">
            <strong>Next Steps:</strong><br>
            1. Verify all checks pass above<br>
            2. Test OTP send with mobile number<br>
            3. Go to: <a href="views/otp_login.php" style="color: #007bff;">views/otp_login.php</a> for full UI<br>
            4. Delete this file after testing
        </p>
    </div>
</body>
</html>
