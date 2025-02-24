<?php

include('config.php'); // Ensure this is correct and loads the connection properly

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $idnumber = trim($_POST["idnumber"]);
    $password = trim($_POST["password"]);
    $created_at = trim($_POST["created_at"]);
    $role = trim($_POST["role"]);
    $gender = trim($_POST["gender"]);
    
    // Set status based on role
    $status = ($role === "Teacher") ? "pending" : "active";

    // Validate fields
    if (empty($firstname) || empty($lastname) || empty($idnumber) || empty($password) || empty($gender)) {
        echo "<script>alert('All fields are required!');</script>";
        echo "<script>window.location.href = 'register.php';</script>";
        exit;
    }

    // Hash the password for security

    // Check if the connection is still open before preparing the statement
    if (!$connection || $connection->connect_error) {
        die("Database connection error: " . $connection->connect_error);
    }

    // Insert user into database
    $stmt = $connection->prepare("INSERT INTO users (firstname, lastname, idnumber, password, created_at, role, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }

    $stmt->bind_param("ssssssss", $firstname, $lastname, $idnumber, $password, $created_at, $role, $gender, $status);

    if ($stmt->execute()) {
        if ($role === "Teacher") {
            echo "<script>alert('Registration successful! Please wait for admin approval before logging in.');</script>";
        } else {
            echo "<script>alert('Registration successful!');</script>";
        }
        echo "<script>window.location.href = 'index.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connections
    $stmt->close();
    $connection->close(); // Close only after all operations are done
}
?>
