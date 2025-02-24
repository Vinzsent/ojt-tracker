<?php
include('config.php');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = mysqli_real_escape_string($connection, $_POST['user_id']);
    $idnumber = mysqli_real_escape_string($connection, $_POST['idnumber']);
    $firstname = mysqli_real_escape_string($connection, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($connection, $_POST['lastname']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);

    // Check if ID number exists for another user
    $check_query = "SELECT user_id FROM users WHERE idnumber = ? AND user_id != ?";
    $stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $idnumber, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "ID Number already exists. Please use a different ID number.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Get user's current role to prevent changing admin role
    $role_query = "SELECT role FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($connection, $role_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);

    if ($user_data['role'] === 'Admin') {
        // For admin users, don't update status
        $update_query = "UPDATE users SET idnumber = ?, firstname = ?, lastname = ?, password = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "ssssi", $idnumber, $firstname, $lastname, $password, $user_id);
    } else {
        // For non-admin users, update everything including status
        $update_query = "UPDATE users SET idnumber = ?, firstname = ?, lastname = ?, password = ?, status = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "sssssi", $idnumber, $firstname, $lastname, $password, $status, $user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "User information updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating user information.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: admin_dashboard.php");
exit();
