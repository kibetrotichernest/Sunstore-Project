<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remember me cookie
setcookie('remember_token', '', time() - 3600, "/");

// Redirect to home page
header("Location: /sunstore-industries/index.php");
exit();
?>