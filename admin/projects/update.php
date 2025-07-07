<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = intval($_POST['id']);
    
    // Process form data
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $category = $conn->real_escape_string($_POST['category']);
    $location = $conn->real_escape_string($_POST['location']);
    $status = $conn->real_escape_string($_POST['status']);
    $size = floatval($_POST['size']);
    $date_completed = $conn->real_escape_string($_POST['date_completed']);
    $video = $conn->real_escape_string($_POST['video']);
    $specs = json_encode(array_filter(array_map('trim', explode("\n", $_POST['specs']))));
    
    // Handle file upload
    $image_name = $_POST['current_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/sunstore-industries/assets/projects/";
        
        // Delete old image if exists
        if ($image_name && file_exists($target_dir . $image_name)) {
            unlink($target_dir . $image_name);
        }
        
        // Upload new image
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $image_name = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $image_name;
        
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }
    
    // Update database
    $sql = "UPDATE projects SET 
            title = ?,
            description = ?,
            category = ?,
            location = ?,
            status = ?,
            size = ?,
            date_completed = ?,
            image = ?,
            video = ?,
            specs = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssdssssi", 
        $title, $description, $category, $location,
        $status, $size, $date_completed, $image_name,
        $video, $specs, $project_id
    );
    
    if ($stmt->execute()) {
        header("Location: view.php?success=updated");
    } else {
        die("Error updating project: " . $conn->error);
    }
} else {
    header("Location: view.php");
}
?>