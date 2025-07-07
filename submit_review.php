<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: customer_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $user_id = (int)$_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    // Validate input
    if ($rating < 1 || $rating > 5 || empty($title) || empty($content)) {
        $_SESSION['error'] = "Please fill all fields correctly";
        header("Location: product.php?id=$product_id#reviews");
        exit();
    }
    
    // Check if user already reviewed this product
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "You've already reviewed this product";
        header("Location: product.php?id=$product_id#reviews");
        exit();
    }
    
    // Insert review
    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, title, content) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $product_id, $user_id, $rating, $title, $content);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Thank you for your review!";
    } else {
        $_SESSION['error'] = "Error submitting review. Please try again.";
    }
    
    header("Location: product.php?id=$product_id#reviews");
    exit();
}

header('Location: product.php');
exit();
?>