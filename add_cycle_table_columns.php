<?php
// Script to add missing columns to cycle_adds table
header('Content-Type: application/json');
require_once('config/database.php');

$response = array();

// SQL statements to add missing columns
$alterStatements = array(
    "ALTER TABLE cycle_adds ADD COLUMN IF NOT EXISTS model VARCHAR(100) AFTER brand",
    "ALTER TABLE cycle_adds ADD COLUMN IF NOT EXISTS product_type VARCHAR(100) AFTER model",
    "ALTER TABLE cycle_adds ADD COLUMN IF NOT EXISTS security_deposit DECIMAL(10,2) DEFAULT 0 AFTER product_type"
);

try {
    foreach($alterStatements as $sql) {
        if($conn->query($sql)) {
            $response['executed'][] = $sql;
        } else {
            $response['errors'][] = array(
                'sql' => $sql,
                'error' => $conn->error
            );
        }
    }
    
    // Get current table structure
    $result = $conn->query("DESCRIBE cycle_adds");
    $columns = array();
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Table alteration completed';
    $response['current_columns'] = $columns;
    http_response_code(200);
} catch(Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
$conn->close();
?>
