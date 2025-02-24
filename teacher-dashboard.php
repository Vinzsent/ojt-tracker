<?php
include('config.php');
session_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');

// Get teacher's information
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($connection, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($user_result);

// Get count of all students
$students_query = "SELECT COUNT(*) as student_count FROM users WHERE role = 'Student'";
$result = mysqli_query($connection, $students_query);
$student_count = mysqli_fetch_assoc($result)['student_count'];

// Get students who logged time today
$active_today_query = "SELECT COUNT(DISTINCT u.user_id) as active_today
    FROM users u
    JOIN time_records tr ON u.user_id = tr.user_id
    WHERE u.role = 'Student' AND DATE(tr.time_in) = CURRENT_DATE";
$result = mysqli_query($connection, $active_today_query);
$active_today = mysqli_fetch_assoc($result)['active_today'];

// Get students currently active
$currently_active_query = "SELECT COUNT(DISTINCT u.user_id) as currently_active
    FROM users u
    JOIN time_records tr ON u.user_id = tr.user_id
    WHERE u.role = 'Student' AND tr.time_out IS NULL";
$result = mysqli_query($connection, $currently_active_query);
$currently_active = mysqli_fetch_assoc($result)['currently_active'];

// Get recent student activities
$recent_activities_query = "SELECT 
    u.firstname,
    u.lastname,
    tr.date,
    TIME_FORMAT(tr.time_in, '%H:%i:%s') as formatted_time_in,
    TIME_FORMAT(tr.time_out, '%H:%i:%s') as formatted_time_out,
    TIMESTAMPDIFF(MINUTE, tr.time_in, IFNULL(tr.time_out, NOW())) as duration_minutes
    FROM users u
    JOIN time_records tr ON u.user_id = tr.user_id
    WHERE u.role = 'Student'
    ORDER BY tr.date DESC, tr.time_in DESC
    LIMIT 10";
$recent_activities = mysqli_query($connection, $recent_activities_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --dcc-green: #0B4619;
            --dcc-gold: #FFB800;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background-color: var(--dcc-green);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--dcc-green);
            border-color: var(--dcc-green);
        }
        
        .btn-primary:hover {
            background-color: #083714;
            border-color: #083714;
        }
        
        .stats-card {
            border-left: 5px solid var(--dcc-gold);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            font-size: 2rem;
            color: var(--dcc-green);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dcc-green);
        }
        
        .activity-table th {
            background-color: var(--dcc-green);
            color: white;
        }

        .activity-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-active {
            background-color: #28a745;
        }

        .status-completed {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">OJT Tracker - Teacher Portal</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Welcome, <?php echo htmlspecialchars($userData["firstname"]); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Students</h6>
                                <div class="stats-number"><?php echo $student_count; ?></div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Active Today</h6>
                                <div class="stats-number"><?php echo $active_today; ?></div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Currently Active</h6>
                                <div class="stats-number"><?php echo $currently_active; ?></div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Quick Actions</h5>
                        <div class="d-flex gap-2">
                            <a href="view-students.php" class="btn btn-primary">
                                <i class="fas fa-users me-2"></i>View Students
                            </a>
                            <a href="student-progress.php" class="btn btn-primary">
                                <i class="fas fa-chart-line me-2"></i>Progress Reports
                            </a>
                            <a href="manage-attendance.php" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Manage Attendance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Recent Student Activities</h5>
                        <div class="table-responsive">
                            <table class="table activity-table">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Date</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($activity = mysqli_fetch_assoc($recent_activities)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($activity['firstname'] . ' ' . $activity['lastname']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($activity['date'])); ?></td>
                                            <td><?php echo $activity['formatted_time_in']; ?></td>
                                            <td><?php echo $activity['formatted_time_out'] ?? 'Active'; ?></td>
                                            <td>
                                                <?php 
                                                    $hours = floor($activity['duration_minutes'] / 60);
                                                    $minutes = $activity['duration_minutes'] % 60;
                                                    echo $hours . 'h ' . $minutes . 'm';
                                                ?>
                                            </td>
                                            <td>
                                                <span class="activity-status <?php echo $activity['formatted_time_out'] ? 'status-completed' : 'status-active'; ?>"></span>
                                                <?php echo $activity['formatted_time_out'] ? 'Completed' : 'Active'; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
