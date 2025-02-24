<?php
include('config.php');

// Create narrative_reports table
$sql = "CREATE TABLE IF NOT EXISTS narrative_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_date DATE NOT NULL,
    report_content TEXT,
    file_path VARCHAR(255),
    original_filename VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if (mysqli_query($connection, $sql)) {
    echo "Narrative reports table created successfully";
} else {
    echo "Error creating table: " . mysqli_error($connection);
}

// Add original_filename column if it doesn't exist
$check_column = "SHOW COLUMNS FROM narrative_reports LIKE 'original_filename'";
$result = mysqli_query($connection, $check_column);

if (mysqli_num_rows($result) == 0) {
    $alter_sql = "ALTER TABLE narrative_reports ADD COLUMN original_filename VARCHAR(255) AFTER file_path";
    if (mysqli_query($connection, $alter_sql)) {
        echo "Added original_filename column successfully";
    } else {
        echo "Error adding column: " . mysqli_error($connection);
    }
}
?>
