<?php
// =============================================================================
// Smart Campus Student Portal - Logout
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['student']);
unset($_SESSION['student_token']);

header("Location: index.php");
exit();
?>
