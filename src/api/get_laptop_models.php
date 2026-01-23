<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

// Get brand from query parameter (optional)
$brand = isset($_GET['brand']) ? mysqli_real_escape_string($conn, $_GET['brand']) : '';

// Get laptop models - if brand specified, filter by brand; otherwise return all models
if(!empty($brand)) {
    $sql = "SELECT title FROM laptop_adds WHERE title LIKE '$brand %' AND title IS NOT NULL ORDER BY title";
} else {
    // Get all models from all brands
    $sql = "SELECT title FROM laptop_adds WHERE title IS NOT NULL ORDER BY title";
}

$result = $conn->query($sql);

if(!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
    $conn->close();
    exit;
}

$models = array();
while($row = $result->fetch_assoc()) {
    $title = $row['title'];
    // Extract product type (second word, before the dash)
    // Title format: "Brand ProductType - AdTitle"
    $parts = explode(' - ', $title);
    $first_part = $parts[0]; // "Brand ProductType"
    $words = explode(' ', $first_part);
    if(count($words) > 1) {
        $model = $words[1]; // ProductType
        if(!in_array($model, $models)) {
            $models[] = $model;
        }
    }
}

sort($models);
$response['success'] = true;
$response['data'] = $models;

echo json_encode($response);
$conn->close();
?>
