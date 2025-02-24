<?php
include('config.php');
session_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$current_time = date('Y-m-d H:i:s');
$current_date = date('Y-m-d');

if ($action === 'time_in') {
    // Check if there's any active time record
    $check_active_query = "SELECT * FROM time_records WHERE user_id = ? AND time_out IS NULL";
    $stmt = mysqli_prepare($connection, $check_active_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have an active time record. Please time out first.']);
        exit();
    }

    // Check total hours completed
    $total_hours_query = "SELECT COALESCE(SUM(
        TIMESTAMPDIFF(MINUTE, time_in, time_out)
    ), 0) as total_minutes 
    FROM time_records 
    WHERE user_id = ? 
    AND time_out IS NOT NULL";
    
    $stmt = mysqli_prepare($connection, $total_hours_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_minutes = $row['total_minutes'];
    
    // 408 hours = 24480 minutes (8 hours × 51 days × 60 minutes)
    if ($total_minutes >= 24480) {
        echo json_encode(['success' => false, 'message' => 'You have completed your required 408 hours (51 days × 8 hours).']);
        exit();
    }

    // Create new time record
    $insert_query = "INSERT INTO time_records (user_id, time_in, date) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($connection, $insert_query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $current_time, $current_date);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'remaining_minutes' => 24480 - $total_minutes
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

} elseif ($action === 'time_out') {
    // Check if there's an active time record
    $check_query = "SELECT * FROM time_records WHERE user_id = ? AND time_out IS NULL";
    $stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'No active time record found']);
        exit();
    }

    // Update the time record
    $update_query = "UPDATE time_records SET time_out = ? WHERE user_id = ? AND time_out IS NULL";
    $stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $current_time, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Calculate remaining hours after this time out
        $total_hours_query = "SELECT COALESCE(SUM(
            TIMESTAMPDIFF(MINUTE, time_in, time_out)
        ), 0) as total_minutes 
        FROM time_records 
        WHERE user_id = ? 
        AND time_out IS NOT NULL";
        
        $stmt = mysqli_prepare($connection, $total_hours_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_minutes = $row['total_minutes'];
        
        echo json_encode([
            'success' => true,
            'total_minutes' => $total_minutes,
            'remaining_minutes' => max(0, 24480 - $total_minutes)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
