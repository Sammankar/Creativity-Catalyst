<?php
session_start();

// Unset all session variables
session_unset();
$_SESSION = [];

// Destroy the session
session_destroy();

// Remove the "Remember Me" cookie if it exists
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, "/"); // Expire the cookie
}

// Prevent browser from caching pages
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect to login page
header("Location: index.php");
exit();
?>
