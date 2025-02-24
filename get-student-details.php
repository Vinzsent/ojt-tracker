<?php
include('config.php');
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    die("Unauthorized access");
}

if (!isset($_GET['user_id'])) {
    die("No student specified");
}

$user_id = mysqli_real_escape_string($connection, $_GET['user_id']);

// Get student details
$student_query = "SELECT * FROM users WHERE user_id = ? AND role = 'Student'";
$stmt = mysqli_prepare($connection, $student_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("Student not found");
}

// Get time records for the last 30 days
$time_records_query = "
    SELECT 
        DATE(time_in) as date,
        TIME_FORMAT(MIN(time_in), '%H:%i:%s') as first_time_in,
        TIME_FORMAT(MAX(time_out), '%H:%i:%s') as last_time_out,
        COUNT(*) as sessions_count,
        SUM(TIMESTAMPDIFF(MINUTE, time_in, IFNULL(time_out, NOW()))) as total_minutes
    FROM time_records
    WHERE user_id = ?
    AND time_in >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY DATE(time_in)
    ORDER BY date DESC";

$stmt = mysqli_prepare($connection, $time_records_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$time_records = mysqli_stmt_get_result($stmt);

// Get total statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT DATE(time_in)) as total_days,
        COALESCE(SUM(TIMESTAMPDIFF(MINUTE, time_in, IFNULL(time_out, NOW()))) / 60, 0) as total_hours,
        COUNT(*) as total_sessions
    FROM time_records
    WHERE user_id = ?";

$stmt = mysqli_prepare($connection, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Calculate remaining days and hours
$required_days = 51;
$required_hours = 408;
$remaining_days = $required_days - $stats['total_days'];
$remaining_hours = $required_hours - $stats['total_hours'];
?>

<div class="container-fluid p-0">
    <!-- Student Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h5>Student Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Name:</th>
                    <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                </tr>
                <tr>
                    <th>ID Number:</th>
                    <td><?php echo htmlspecialchars($student['idnumber']); ?></td>
                </tr>
                <tr>
                    <th>Gender:</th>
                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h5>Progress Summary</h5>
            <table class="table table-sm">
                <tr>
                    <th>Total Days:</th>
                    <td>
                        <?php echo $stats['total_days']; ?> / <?php echo $required_days; ?> days
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo ($stats['total_days'] / $required_days) * 100; ?>%">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>Total Hours:</th>
                    <td>
                        <?php echo number_format($stats['total_hours'], 1); ?> / <?php echo $required_hours; ?> hours
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo ($stats['total_hours'] / $required_hours) * 100; ?>%">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>Remaining:</th>
                    <td>
                        <?php echo $remaining_days; ?> days (<?php echo number_format($remaining_hours, 1); ?> hours)
                    </td>
                </tr>
                <tr>
                    <th>Total Sessions:</th>
                    <td><?php echo $stats['total_sessions']; ?> sessions</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Recent Activity -->
    <h5>Recent Activity (Last 30 Days)</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>First Time In</th>
                    <th>Last Time Out</th>
                    <th>Sessions</th>
                    <th>Total Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($record = mysqli_fetch_assoc($time_records)): 
                    $hours = floor($record['total_minutes'] / 60);
                    $minutes = $record['total_minutes'] % 60;
                    ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                        <td><?php echo $record['first_time_in']; ?></td>
                        <td><?php echo $record['last_time_out'] ?? 'Active'; ?></td>
                        <td><?php echo $record['sessions_count']; ?></td>
                        <td><?php echo $hours . 'h ' . $minutes . 'm'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
