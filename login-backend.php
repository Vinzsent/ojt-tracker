<?php
include("config.php");

session_start();

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idnumber = $_POST["idnumber"];
    $password = $_POST["password"];
    $status = $_POST["status"];
    
    $sql = "SELECT * FROM users WHERE idnumber=?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if ($password === $row["password"]) {
            // Check account status for non-admin users
            if ($row['role'] !== 'Admin') {
                if ($row['status'] === 'pending') {
                    echo '<script>';
                    echo 'alert("Your account is pending approval. Please wait for admin confirmation.");';
                    echo 'window.location.href = "index.php";';
                    echo '</script>';
                    exit();
                } elseif ($row['status'] === 'rejected') {
                    echo '<script>';
                    echo 'alert("Your account has been rejected. Please contact the administrator.");';
                    echo 'window.location.href = "index.php";';
                    echo '</script>';
                    exit();
                }
            }

            // Get user_id first
            $user_id = $row['user_id'];
            
            // Set all session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['idnumber'] = $row['idnumber'];
            $_SESSION['lastname'] = $row['lastname'];
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['role'] = $row['role'];
            
            // Update status to online
            $status_query = "UPDATE users SET status = 'online' WHERE user_id = ?";
            $status_stmt = $connection->prepare($status_query);
            $status_stmt->bind_param("i", $user_id);
            $status_stmt->execute();
            $status_stmt->close();

            // Redirect based on role
            if ($row['role'] === 'Admin') {
                header("Location: admin_dashboard.php");
                exit();
            } elseif ($row['role'] === 'Teacher') {
                header("Location: teacher-dashboard.php");
                exit();
            } elseif ($row['role'] === 'Student') {
                header("Location: student-dashboard.php");
                exit();
            }
        } else {
            echo '<script>';
            echo 'alert("Invalid Username or Password");';
            echo 'window.location.href = "index.php";';
            echo '</script>';
            exit();
        }
    } else {
        echo '<script>';
        echo 'alert("Invalid Username or Password");';
        echo 'window.location.href = "index.php";';
        echo '</script>';
        exit();
    }

    $stmt->close();
}

$connection->close();
?>