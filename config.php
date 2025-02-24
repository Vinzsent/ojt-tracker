<?php
$host = "sql305.infinityfree.com";
$user = "if0_38349757";  // Change if necessary
$pass = "cramecrame";  // Change if necessary
$dbname = "if0_38349757_ojt";  // Your database name

$connection = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>
