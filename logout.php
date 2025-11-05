<?php
session_start();

// Destroy all session data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect with message
header("Location: msg.php?msg=You have been logged out successfully.&type=success&goto=login.php");
exit();
?>
