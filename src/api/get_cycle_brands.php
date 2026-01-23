<?php
header('Content-Type: application/json');
require_once('../common/db.php');

$response = array();

// Get cycle brands by extracting from title field
// Title format: "Brand ProductType - AdTitle"
$sql = "SELECT title FROM cycle_adds WHERE title IS NOT NULL AND title != '' ORDER BY title";
$result = $conn->query($sql);

if($result) {
    $brands = array();
    while($row = $result->fetch_assoc()) {
        $title = $row['title'];
        // Extract brand (first word before space)
        $parts = explode(' ', $title);
        if(!empty($parts[0])) {
            $brand = $parts[0];
            if(!in_array($brand, $brands)) {
                $brands[] = $brand;
            }
        }
    }
    sort($brands);
    $response['success'] = true;
    $response['data'] = $brands;
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $conn->error;
}

echo json_encode($response);
$conn->close();
?>
