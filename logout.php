<?php
session_start(); // Start the session

include('config.php');

$user_id = $_SESSION['user_id'];

$status_query = "UPDATE users SET status = 'offline' WHERE user_id = '$user_id'";
$status_result = mysqli_query($connection, $status_query);

// Destroy all session variables
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;


?>
