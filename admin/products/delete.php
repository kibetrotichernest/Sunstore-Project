<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: view.php');
    exit;
}

$product_id = intval($_GET['id']);

// Fetch product to get image path
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($product) {
    // Delete product from database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        // Delete associated image if it exists
        if ($product['image'] && file_exists('../../' . $product['image'])) {
            unlink('../../' . $product['image']);
        }
        
        $_SESSION['message'] = 'Product deleted successfully';
    } else {
        $_SESSION['error'] = 'Error deleting product';
    }
    $stmt->close();
}

header('Location: view.php');
exit;