<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $content = $_POST['content'] ?? '';
    $color = $_POST['color'] ?? '#ffeb3b';
    
    $query = "INSERT INTO notes (user_id, content, color, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $content, $color);
    
    if (mysqli_stmt_execute($stmt)) {
        $note_id = mysqli_insert_id($connection);
        echo json_encode(['success' => true, 'note_id' => $note_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add note']);
    }

} elseif ($action === 'delete') {
    $note_id = $_POST['note_id'] ?? '';
    
    $query = "DELETE FROM notes WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ii", $note_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete note']);
    }

} elseif ($action === 'update') {
    $note_id = $_POST['note_id'] ?? '';
    $content = $_POST['content'] ?? '';
    $color = $_POST['color'] ?? '';
    
    $query = "UPDATE notes SET content = ?, color = ? WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ssii", $content, $color, $note_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update note']);
    }
}
?>
