<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a longer session timeout (30 days for remember me, 2 hours for regular)
$session_timeout = isset($_SESSION['remember_me']) && $_SESSION['remember_me'] ? 30 * 24 * 60 * 60 : 2 * 60 * 60;

// Update session activity timestamp
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    } else {
        // Check if session has expired
        if ((time() - $_SESSION['last_activity']) > $session_timeout) {
            // Session expired
            session_unset();
            session_destroy();
            header('Location: login.php?session_expired=1');
            exit();
        }
    }
    // Update last activity
    $_SESSION['last_activity'] = time();
}

// Set session cookie parameters for persistence
session_set_cookie_params([
    'lifetime' => $session_timeout,
    'path' => '/',
    'httponly' => true,
    'secure' => false, // Set to true if using HTTPS
    'samesite' => 'Lax'
]);
?>