<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../common/db.php');

$response = array();

// Debug: Check database connection
if(!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed', 'error' => mysqli_connect_error()]);
    exit;
}

if($_SERVER['REQUEST_METHOD'] != 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET method allowed']);
    exit;
}

// Get all side table ads
$sql = "SELECT id, user_id, title, description, `price` as `price_per_month`, city, latitude, longitude, image_url, created_at, updated_at FROM sidetable_adds ORDER BY created_at DESC";

$result = $conn->query($sql);
if(!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query failed', 'error' => $conn->error]);
    exit;
}

$sidetable_data = array();
while($row = $result->fetch_assoc()) {
    $sidetable_data[] = $row;
}

$response['success'] = true;
$response['message'] = 'Side table ads retrieved successfully';
$response['data'] = $sidetable_data;
$response['count'] = count($sidetable_data);

http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
