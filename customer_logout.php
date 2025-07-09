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
// Explicitly unset customer session variables
unset(
    $_SESSION['customer_id'],
    $_SESSION['first_name'],
    $_SESSION['last_name'],
    $_SESSION['email'],
    $_SESSION['logged_in']
);
// Destroy the session
session_destroy();

// Clear any remember me cookie
setcookie('remember_token', '', time() - 3600, "/");

// Redirect to home page
header("Location: /Sunstore-Project/index.php");
exit();
