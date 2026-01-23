<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

// Get brand from query parameter (truly optional)
$brand = isset($_GET['brand']) && !empty($_GET['brand']) ? mysqli_real_escape_string($conn, $_GET['brand']) : false;

// Get car models - if brand specified, filter by brand; otherwise return all models
if($brand !== false) {
    // Filter by specific brand
    $sql = "SELECT DISTINCT title FROM car_adds WHERE title LIKE '" . $brand . " %' AND title IS NOT NULL ORDER BY title";
} else {
    // Return ALL car models from all brands - NO BRAND FILTER
    $sql = "SELECT DISTINCT title FROM car_adds WHERE title IS NOT NULL ORDER BY title";
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
