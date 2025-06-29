<?php
$host = "localhost";
$user = "root";  // Change if necessary
$pass = "";  // Change if necessary
$dbname = "ojt";  // Your database name

$connection = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>
