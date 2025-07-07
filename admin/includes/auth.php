<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['admin_id']) &&
           $_SESSION['IP_ADDRESS'] === $_SERVER['REMOTE_ADDR'] &&
           $_SESSION['USER_AGENT'] === $_SERVER['HTTP_USER_AGENT'];
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
    
    // Session timeout (15 minutes)
    if (time() - $_SESSION['LAST_ACTIVITY'] > 900) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    
    $_SESSION['LAST_ACTIVITY'] = time();
}

function require_role(string $role): void {
    require_login();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        exit("Access denied. Insufficient privileges.");
    }
}
?>