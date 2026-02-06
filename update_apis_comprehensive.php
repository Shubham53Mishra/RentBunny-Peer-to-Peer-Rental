<?php
// Comprehensive update script for all API files

$api_dir = 'c:/xampp/htdocs/Rent/src/api/';

// Files to skip
$skip_files = ['update_exercise_bike_add.php', 'update_aadhaar.php', 'update_pan.php'];

// Get all update_*_add.php and update_dl.php files
$files_to_process = [
    'update_ac_add.php',
    'update_bed_add.php',
    'update_bike_add.php',
    'update_cajon_add.php',
    'update_camera_add.php',
    'update_car_add.php',
    'update_chimney_add.php',
    'update_clothing_add.php',
    'update_computer_add.php',
    'update_crosstrainer_add.php',
    'update_crutches_add.php',
    'update_cycle_add.php',
    'update_dining_add.php',
    'update_dl.php',
    'update_drum_add.php',
    'update_guitar_add.php',
    'update_harmonium_add.php',
    'update_hob_add.php',
    'update_keyboard_add.php',
    'update_massager_add.php',
    'update_mixer_add.php',
    'update_printer_add.php',
    'update_purifier_add.php',
    'update_refrigerator_add.php',
    'update_sidetable_add.php',
    'update_sofa_add.php',
    'update_tabla_add.php',
    'update_treadmill_add.php',
    'update_tv_add.php',
    'update_violin_add.php',
    'update_washing_machine_add.php',
    'update_wheelchair_add.php'
];

function extractTableName($content) {
    if(preg_match('/\$table_name\s*=\s*[\'"]([^\'"]+)[\'"]/i', $content, $matches)) {
        return $matches[1];
    }
    return null;
}

function generateUpdatedCode($original_content, $table_name, $filename) {
    // Extract specific info from original
    $ad_name = ucfirst(str_replace(['update_', '_add.php', 'update_dl.php'], '', $filename));
    $ad_name = str_replace('_', ' ', $ad_name);
    
    // Build the new code based on the template
    $new_code = '<?php' . "\n";
    $new_code .= "header('Content-Type: application/json');\n";
    $new_code .= "error_reporting(E_ALL);\n";
    $new_code .= "ini_set('display_errors', 1);\n";
    $new_code .= "require_once('../common/db.php');\n\n";
    
    $new_code .= "\$response = array();\n\n";
    
    $new_code .= "// Debug: Check database connection\n";
    $new_code .= "if(!\$conn) {\n";
    $new_code .= "    http_response_code(500);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Database connection failed', 'error' => mysqli_connect_error()]);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "// Get token from header\n";
    $new_code .= "\$token = '';\n";
    $new_code .= "if(function_exists('getallheaders')) {\n";
    $new_code .= "    \$headers = getallheaders();\n";
    $new_code .= "    \$token = isset(\$headers['token']) ? \$headers['token'] : (isset(\$headers['Token']) ? \$headers['Token'] : '');\n";
    $new_code .= "}\n";
    $new_code .= "if(empty(\$token) && isset(\$_SERVER['HTTP_AUTHORIZATION'])) {\n";
    $new_code .= "    \$token = str_replace('Bearer ', '', \$_SERVER['HTTP_AUTHORIZATION']);\n";
    $new_code .= "}\n";
    $new_code .= "if(empty(\$token) && isset(\$_SERVER['HTTP_TOKEN'])) {\n";
    $new_code .= "    \$token = \$_SERVER['HTTP_TOKEN'];\n";
    $new_code .= "}\n";
    $new_code .= "if(empty(\$token) && isset(\$_GET['token'])) {\n";
    $new_code .= "    \$token = \$_GET['token'];\n";
    $new_code .= "}\n\n";
    
    $new_code .= "if(empty(\$token)) {\n";
    $new_code .= "    http_response_code(401);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Token not provided']);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "\$token = mysqli_real_escape_string(\$conn, \$token);\n";
    $new_code .= "\$sql = \"SELECT id FROM users WHERE token = '\$token' AND token IS NOT NULL\";\n";
    $new_code .= "\$result = \$conn->query(\$sql);\n";
    $new_code .= "if(!\$result) {\n";
    $new_code .= "    http_response_code(500);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Database query failed', 'error' => \$conn->error]);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n";
    $new_code .= "if(\$result->num_rows == 0) {\n";
    $new_code .= "    http_response_code(401);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Invalid token']);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "\$user_row = \$result->fetch_assoc();\n";
    $new_code .= "\$user_id = \$user_row['id'];\n\n";
    
    $new_code .= "if(\$_SERVER['REQUEST_METHOD'] != 'POST') {\n";
    $new_code .= "    http_response_code(405);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "// Required field: add_id\n";
    $new_code .= "\$input = json_decode(file_get_contents('php://input'), true);\n\n";
    
    $new_code .= "// Check if JSON parsing failed\n";
    $new_code .= "if(\$input === null && file_get_contents('php://input') !== '') {\n";
    $new_code .= "    http_response_code(400);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Invalid JSON format', 'error' => json_last_error_msg()]);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "if(!is_array(\$input)) {\n";
    $new_code .= "    http_response_code(400);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'No JSON data received', 'received_data' => gettype(\$input)]);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "// Check if add_id is provided\n";
    $new_code .= "if(!isset(\$input['add_id'])) {\n";
    $new_code .= "    http_response_code(400);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Missing required field: add_id', 'received_fields' => array_keys(\$input)]);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "\$add_id = intval(\$input['add_id']);\n";
    $new_code .= "\$table_name = '$table_name';\n\n";
    
    $new_code .= "// Check if ad exists and belongs to current user\n";
    $new_code .= "\$check_sql = \"SELECT id, user_id FROM \$table_name WHERE id = '\$add_id'\";\n";
    $new_code .= "\$check_result = \$conn->query(\$check_sql);\n\n";
    
    $new_code .= "if(!\$check_result || \$check_result->num_rows == 0) {\n";
    $new_code .= "    http_response_code(404);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Ad not found']);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "\$ad_row = \$check_result->fetch_assoc();\n";
    $new_code .= "if(\$ad_row['user_id'] != \$user_id) {\n";
    $new_code .= "    http_response_code(403);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'Unauthorized: You can only update your own ads']);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "// Build update query with only provided fields\n";
    $new_code .= "\$update_fields = [];\n";
    $new_code .= "\$updates = [];\n\n";
    
    $new_code .= "// List of allowed fields to update\n";
    $new_code .= "\$allowed_fields = ['title', 'description', 'price', 'condition', 'city', 'latitude', 'longitude', 'image_url', 'brand', 'product_type'];\n\n";
    
    $new_code .= "foreach(\$allowed_fields as \$field) {\n";
    $new_code .= "    if(isset(\$input[\$field])) {\n";
    $new_code .= "        \$value = \$input[\$field];\n";
    $new_code .= "        \n";
    $new_code .= "        // Special handling for image_urls array\n";
    $new_code .= "        if(\$field === 'image_url' && is_array(\$value)) {\n";
    $new_code .= "            \$validated_urls = array();\n";
    $new_code .= "            foreach(\$value as \$url) {\n";
    $new_code .= "                if(filter_var(\$url, FILTER_VALIDATE_URL)) {\n";
    $new_code .= "                    \$validated_urls[] = mysqli_real_escape_string(\$conn, \$url);\n";
    $new_code .= "                }\n";
    $new_code .= "            }\n";
    $new_code .= "            \$image_urls = !empty(\$validated_urls) ? json_encode(\$validated_urls) : '';\n";
    $new_code .= "            \$updates[] = \"\`image_url\` = '\$image_urls'\";\n";
    $new_code .= "            continue;\n";
    $new_code .= "        }\n";
    $new_code .= "        \n";
    $new_code .= "        // Type-specific sanitization\n";
    $new_code .= "        if(in_array(\$field, ['price', 'latitude', 'longitude'])) {\n";
    $new_code .= "            \$value = floatval(\$value);\n";
    $new_code .= "        } else {\n";
    $new_code .= "            \$value = mysqli_real_escape_string(\$conn, \$value);\n";
    $new_code .= "        }\n";
    $new_code .= "        \n";
    $new_code .= "        // Validate numeric values\n";
    $new_code .= "        if(\$field === 'price' && \$value <= 0) {\n";
    $new_code .= "            http_response_code(400);\n";
    $new_code .= "            echo json_encode(['success' => false, 'message' => 'price must be greater than 0']);\n";
    $new_code .= "            exit;\n";
    $new_code .= "        }\n";
    $new_code .= "        if(\$field === 'latitude' && (\$value < -90 || \$value > 90)) {\n";
    $new_code .= "            http_response_code(400);\n";
    $new_code .= "            echo json_encode(['success' => false, 'message' => 'Invalid latitude value']);\n";
    $new_code .= "            exit;\n";
    $new_code .= "        }\n";
    $new_code .= "        if(\$field === 'longitude' && (\$value < -180 || \$value > 180)) {\n";
    $new_code .= "            http_response_code(400);\n";
    $new_code .= "            echo json_encode(['success' => false, 'message' => 'Invalid longitude value']);\n";
    $new_code .= "            exit;\n";
    $new_code .= "        }\n";
    $new_code .= "        \n";
    $new_code .= "        \$updates[] = \"\`\$field\` = '\$value'\";\n";
    $new_code .= "        \$update_fields[\$field] = \$value;\n";
    $new_code .= "    }\n";
    $new_code .= "}\n\n";
    
    $new_code .= "// Check if at least one field is being updated\n";
    $new_code .= "if(empty(\$updates)) {\n";
    $new_code .= "    http_response_code(400);\n";
    $new_code .= "    echo json_encode(['success' => false, 'message' => 'No fields to update. Provide at least one field to update.']);\n";
    $new_code .= "    exit;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "// Add updated_at timestamp\n";
    $new_code .= "\$updates[] = \"\`updated_at\` = NOW()\";\n\n";
    
    $new_code .= "// Build and execute update query\n";
    $new_code .= "\$update_sql = \"UPDATE \$table_name SET \" . implode(', ', \$updates) . \" WHERE id = '\$add_id'\";\n\n";
    
    $new_code .= "if(\$conn->query(\$update_sql)) {\n";
    $new_code .= "    \$response['success'] = true;\n";
    $new_code .= "    \$response['message'] = '" . ucfirst($table_name) . " updated successfully';\n";
    $new_code .= "    \$response['data'] = [\n";
    $new_code .= "        'add_id' => \$add_id,\n";
    $new_code .= "        'table' => \$table_name,\n";
    $new_code .= "        'updated_at' => date('Y-m-d H:i:s'),\n";
    $new_code .= "        'updated_fields' => array_keys(\$update_fields)\n";
    $new_code .= "    ];\n";
    $new_code .= "    http_response_code(200);\n";
    $new_code .= "} else {\n";
    $new_code .= "    http_response_code(500);\n";
    $new_code .= "    \$response['success'] = false;\n";
    $new_code .= "    \$response['message'] = 'Failed to update ' . \$table_name;\n";
    $new_code .= "    \$response['error'] = \$conn->error;\n";
    $new_code .= "    \$response['error_code'] = \$conn->errno;\n";
    $new_code .= "    \$response['debug_sql'] = \$update_sql;\n";
    $new_code .= "    \$response['debug_input'] = \$input;\n";
    $new_code .= "}\n\n";
    
    $new_code .= "echo json_encode(\$response, JSON_PRETTY_PRINT);\n";
    $new_code .= "\$conn->close();\n";
    $new_code .= "?>\n";
    
    return $new_code;
}

echo "Starting update process...\n\n";

$updated_count = 0;
$skipped_count = 0;
$failed_count = 0;

foreach($files_to_process as $filename) {
    $filepath = $api_dir . $filename;
    
    if(!file_exists($filepath)) {
        echo "[ERROR] File not found: $filename\n";
        $failed_count++;
        continue;
    }
    
    $original_content = file_get_contents($filepath);
    $table_name = extractTableName($original_content);
    
    if(!$table_name) {
        echo "[SKIP] $filename (could not extract table name)\n";
        $skipped_count++;
        continue;
    }
    
    $new_content = generateUpdatedCode($original_content, $table_name, $filename);
    
    if(file_put_contents($filepath, $new_content) !== false) {
        echo "[OK] $filename -> $table_name\n";
        $updated_count++;
    } else {
        echo "[FAIL] $filename (could not write file)\n";
        $failed_count++;
    }
}

echo "\n\n=== Summary ===\n";
echo "Updated: $updated_count files\n";
echo "Skipped: $skipped_count files\n";
echo "Failed: $failed_count files\n";
?>
