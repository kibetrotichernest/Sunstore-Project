<?php
declare(strict_types=1);
session_start();

require 'includes/config.php';
require 'includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Database connection error");
            }
            
            $stmt = $conn->prepare("SELECT id, username, password, role FROM admin_users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Change this part in login.php
if (password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    
    $_SESSION = [
        'admin_id' => $user['id'],  // Changed from 'user_id' to 'admin_id'
        'username' => $user['username'],
        'admin_name' => $user['full_name'],  // Added to match admin-header
        'admin_role' => $user['role'],  // Changed from 'role' to 'admin_role'
        'LAST_ACTIVITY' => time(),
        'IP_ADDRESS' => $_SERVER['REMOTE_ADDR'],
        'USER_AGENT' => $_SERVER['HTTP_USER_AGENT']
    ];
    
    header("Location: index.php");
    exit();
}
            }
            
            // Generic error message to prevent user enumeration
            $error = "Invalid username or password";
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $error = "System error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; }
        .login-container { max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container mx-auto p-4 bg-white rounded shadow">
            <h2 class="text-center mb-4">Admin Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</body>
</html>