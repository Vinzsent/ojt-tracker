<?php
include('config.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $idnumber = mysqli_real_escape_string($connection, $_POST['idnumber']);
    $firstname = mysqli_real_escape_string($connection, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($connection, $_POST['lastname']);

    // Check if ID number already exists for another user
    $check_query = "SELECT user_id FROM users WHERE idnumber = ? AND user_id != ?";
    $stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $idnumber, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "ID Number already exists. Please use a different ID number.";
        header("Location: student-dashboard.php");
        exit();
    }

    // Handle password update if requested
    $password_update = false;
    if (!empty($_POST['new_password']) && !empty($_POST['current_password'])) {
        // Verify current password
        $current_password = $_POST['current_password'];
        $verify_query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($connection, $verify_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);

        if ($user_data && $current_password === $user_data['password']) {
            $password_update = true;
            $new_password = mysqli_real_escape_string($connection, $_POST['new_password']);
        } else {
            $_SESSION['error'] = "Current password is incorrect. Profile not updated.";
            header("Location: student-dashboard.php");
            exit();
        }
    }

    // Update user information
    if ($password_update) {
        $update_query = "UPDATE users SET idnumber = ?, firstname = ?, lastname = ?, password = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "ssssi", $idnumber, $firstname, $lastname, $new_password, $user_id);
    } else {
        $update_query = "UPDATE users SET idnumber = ?, firstname = ?, lastname = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "sssi", $idnumber, $firstname, $lastname, $user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = $password_update ? 
            "Profile and password updated successfully!" : 
            "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating profile. Please try again.";
    }

    header("Location: student-dashboard.php");
    exit();
} else {
    // If accessed directly without POST data, redirect to dashboard
    header("Location: student-dashboard.php");
    exit();
}
