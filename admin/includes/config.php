<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sunstore_industries');

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

// Site configuration
define('SITE_NAME', 'Sunstore Industries Limited');
define('SITE_URL', 'localhost/sunstore-industries');
define('CURRENCY', 'Ksh');

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function upload_file($file, $target_dir, $allowed_types = [], $max_size = 2097152) {
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $file['error']);
    }

    // Check file size
    if ($file['size'] > $max_size) {
        throw new Exception("File is too large. Maximum size allowed is " . ($max_size / 1024 / 1024) . "MB");
    }

    // Check file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!empty($allowed_types) && !in_array($file_type, $allowed_types)) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_types));
    }

    // Create target directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate unique filename
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_ext;
    $target_path = rtrim($target_dir, '/') . '/' . $filename;

    // Move the file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    } else {
        throw new Exception("Failed to move uploaded file");
    }
}

function upload_multiple_files($files, $target_dir, $allowed_types = [], $max_size = 2097152) {
    $uploaded_files = [];
    
    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            ];
            
            try {
                $uploaded_files[] = upload_file($file, $target_dir, $allowed_types, $max_size);
            } catch (Exception $e) {
                // Skip failed uploads or handle as needed
                continue;
            }
        }
    }
    
    return json_encode($uploaded_files);
}
?>
