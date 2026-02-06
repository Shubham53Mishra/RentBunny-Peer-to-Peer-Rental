<?php
// Script to update all update_*_add.php files with better error handling and image URLs support

$api_dir = __DIR__ . '/src/api/';
$files = array_diff(scandir($api_dir), array('.', '..'));

$update_files = [];
foreach($files as $file) {
    if(preg_match('/^update_(.*?)_add\.php$|^update_dl\.php$/', $file)) {
        $update_files[] = $file;
    }
}

echo "Found " . count($update_files) . " update API files\n\n";
echo "Files to update:\n";
foreach($update_files as $file) {
    echo "  - " . $file . "\n";
}
echo "\n";

// Read the improved exercise bike API as template
$template_file = $api_dir . 'update_exercise_bike_add.php';
if(!file_exists($template_file)) {
    die("ERROR: Template file not found: $template_file\n");
}

$template_content = file_get_contents($template_file);
echo "Template loaded from: $template_file\n";
echo "Template size: " . strlen($template_content) . " bytes\n\n";

// For each file, show the differences
$count = 0;
foreach($update_files as $file) {
    if($file === 'update_exercise_bike_add.php') {
        echo "[SKIP] $file (already updated)\n";
        continue;
    }
    if($file === 'update_aadhaar.php' || $file === 'update_pan.php') {
        echo "[SKIP] $file (document upload, not a product ad)\n";
        continue;
    }
    
    $filepath = $api_dir . $file;
    $content = file_get_contents($filepath);
    
    // Extract table name from the file
    if(preg_match('/\$table_name\s*=\s*[\'"]([^\'"]+)[\'"]/i', $content, $matches)) {
        $table = $matches[1];
        echo "[$count] $file -> table: $table\n";
        $count++;
    } else {
        echo "[$count] $file -> table: NOT FOUND\n";
        $count++;
    }
}

echo "\nReady to update all files. Total to update: " . ($count) . " files\n";
?>
