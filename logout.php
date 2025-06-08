<?php
// mb_logistics/logout.php

session_start(); // Start the session to access session variables

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
// This also deletes the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Destroy the session data on the server

// Redirect to login page after logout
header("location: index.php");
exit; // Terminate script execution after redirection
?>