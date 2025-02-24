<?php
include('config.php');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = mysqli_real_escape_string($connection, $_POST['user_id']);

    // Check if trying to delete an admin
    $check_query = "SELECT role FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user['role'] === 'Admin') {
        $_SESSION['error'] = "Cannot delete admin users.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Delete user's time records first (foreign key constraint)
    $delete_time_records = "DELETE FROM time_records WHERE user_id = ?";
    $stmt = mysqli_prepare($connection, $delete_time_records);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    // Delete the user
    $delete_user = "DELETE FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($connection, $delete_user);
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "User has been deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting user.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: admin_dashboard.php");
exit();
