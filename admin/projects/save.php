<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set default timezone
date_default_timezone_set('Africa/Nairobi');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Validate and prepare all fields
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);
        $location = trim($_POST['location']);
        $status = trim($_POST['status']);
        $date_completed = $_POST['date_completed'];
        $size = (float)$_POST['size'];
        $video = !empty($_POST['video']) ? trim($_POST['video']) : null;

        // Process specifications
        $specs = [];
        if (!empty($_POST['specs'])) {
            $lines = explode("\n", $_POST['specs']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $parts = explode('|', $line, 2);
                    if (count($parts) === 2) {
                        $specs[] = [
                            'name' => trim($parts[0]),
                            'value' => trim($parts[1])
                        ];
                    } elseif (count($parts) === 1) {
                        $specs[] = trim($parts[0]);
                    }
                }
            }
        }
        $specs_json = !empty($specs) ? json_encode($specs) : null;

        // 2. Handle file upload
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Please upload a project image");
        }

        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
        }

        if ($file['size'] > $max_size) {
            throw new Exception("File is too large. Max size is 2MB.");
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $image_name = 'project_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $upload_path = '../assets/projects/' . $image_name;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("Failed to save uploaded file");
        }

        // 3. Prepare and execute the INSERT statement
        $stmt = $conn->prepare("
            INSERT INTO projects (
                title, description, category, location, status,
                image, video, specs, date_completed, size
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sssssssssd",
            $title,
            $description,
            $category,
            $location,
            $status,
            $image_name,
            $video,
            $specs_json,
            $date_completed,
            $size
        );

        if (!$stmt->execute()) {
            // Clean up uploaded file if DB insert fails
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            throw new Exception("Database error: " . $stmt->error);
        }

        // Success - redirect with success message
        $_SESSION['success'] = 'Project added successfully!';
        header('Location: view.php');
        exit();

    } catch (Exception $e) {
        // Clean up uploaded file if error occurred
        if (isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
        }
        
        $_SESSION['error'] = $e->getMessage();
        header('Location: add.php');
        exit();
    }
} else {
    header('Location: add.php');
    exit();
}