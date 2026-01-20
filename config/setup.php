<?php
require_once('database.php');

// Add OTP columns to users table
$columns_to_add = array(
    "otp VARCHAR(6)",
    "otp_created_at TIMESTAMP NULL",
    "otp_expires_at TIMESTAMP NULL",
    "otp_verified BOOLEAN DEFAULT FALSE"
);

foreach($columns_to_add as $column) {
    // Extract column name
    $col_name = explode(' ', $column)[0];
    
    // Check if column already exists
    $check_sql = "SHOW COLUMNS FROM users LIKE '$col_name'";
    $result = $conn->query($check_sql);
    
    if($result->num_rows == 0) {
        $alter_sql = "ALTER TABLE users ADD COLUMN $column";
        if($conn->query($alter_sql) === TRUE) {
            echo "✅ Column '$col_name' added successfully!<br>";
        } else {
            echo "❌ Error adding '$col_name': " . $conn->error . "<br>";
        }
    } else {
        echo "✅ Column '$col_name' already exists!<br>";
    }
}

echo "<hr>";
echo "<h3>Users table structure:</h3>";
$show_sql = "SHOW COLUMNS FROM users";
$result = $conn->query($show_sql);

if($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
