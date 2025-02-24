<?php

include('config.php');

session_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');

$user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($connection, $user_query);
$userData = mysqli_fetch_assoc($user_result);

// Get today's time record if any
$time_query = "SELECT * FROM time_records WHERE user_id = '$user_id' AND date = '$current_date' ORDER BY time_in DESC LIMIT 1";
$time_result = mysqli_query($connection, $time_query);
$timeData = mysqli_fetch_assoc($time_result);

// Calculate total hours (now including minutes)
$total_minutes_query = "SELECT COALESCE(SUM(
    TIMESTAMPDIFF(MINUTE, time_in, time_out)
), 0) as total_minutes 
FROM time_records 
WHERE user_id = ? 
AND time_out IS NOT NULL";

$stmt = mysqli_prepare($connection, $total_minutes_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_minutes = $row['total_minutes'];

$total_hours = floor($total_minutes / 60);
$remaining_minutes = $total_minutes % 60;
$total_hours_display = $total_hours . "h " . $remaining_minutes . "m";

// Calculate progress
$total_required_minutes = 24480; // 51 days × 8 hours × 60 minutes
$progress_percentage = min(100, ($total_minutes / $total_required_minutes) * 100);

// Get current active session if any
$active_session_query = "SELECT time_in FROM time_records WHERE user_id = ? AND time_out IS NULL";
$stmt = mysqli_prepare($connection, $active_session_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$has_active_session = mysqli_num_rows($result) > 0;
$active_since = $has_active_session ? mysqli_fetch_assoc($result)['time_in'] : null;

// Calculate days left (51 days total)
$days_completed_query = "SELECT COUNT(DISTINCT DATE(time_in)) as days_completed FROM time_records WHERE user_id = '$user_id'";
$days_completed_result = mysqli_query($connection, $days_completed_query);
$days_completed_data = mysqli_fetch_assoc($days_completed_result);
$days_completed = $days_completed_data['days_completed'] ?? 0;
$days_left = 51 - $days_completed;

// Get today's duration if currently clocked in
$current_duration = '';
if ($timeData && !isset($timeData['time_out'])) {
    $time_in = new DateTime($timeData['time_in']);
    $now = new DateTime();
    $interval = $time_in->diff($now);
    $current_duration = sprintf(
        '%02d:%02d:%02d',
        $interval->h + ($interval->days * 24),
        $interval->i,
        $interval->s
    );
}

// Get recent time records for history
$history_query = "SELECT 
    date,
    TIME_FORMAT(time_in, '%H:%i:%s') as formatted_time_in,
    TIME_FORMAT(time_out, '%H:%i:%s') as formatted_time_out,
    TIMESTAMPDIFF(MINUTE, time_in, IFNULL(time_out, NOW())) as duration_minutes
    FROM time_records 
    WHERE user_id = '$user_id'
    ORDER BY date DESC, time_in DESC
    LIMIT 5";
$history_result = mysqli_query($connection, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
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
        
        .progress-bar {
            background-color: var(--dcc-green);
        }
        
        .stats-card {
            border-left: 5px solid var(--dcc-gold);
        }
        
        .time-btn {
            width: 150px;
        }

        .history-table th {
            background-color: var(--dcc-green);
            color: white;
        }

        .history-table {
            margin-top: 1rem;
        }

        .total-time {
            font-size: 1.2rem;
            color: var(--dcc-green);
            font-weight: 600;
        }

        .progress {
            height: 1rem;
        }

        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }

        .duration-display {
            font-family: monospace;
            font-size: 1.2rem;
            color: var(--dcc-green);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">OJT Tracker</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">
                    Welcome, <?php echo htmlspecialchars($userData["firstname"]); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Total Progress</h5>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-3">Total Hours: <?php echo $total_hours_display; ?> / 408h</h4>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $progress_percentage; ?>%"
                                 aria-valuenow="<?php echo $progress_percentage; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($progress_percentage, 1); ?>%
                            </div>
                        </div>
                        <p class="mb-0">
                            Remaining: <?php echo floor((24480 - $total_minutes) / 60); ?>h 
                            <?php echo ((24480 - $total_minutes) % 60); ?>m
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Current Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($has_active_session): ?>
                            <div class="alert alert-success">
                                <h5>Currently On Duty</h5>
                                <p class="mb-0">Started: <?php echo date('M d, Y h:i A', strtotime($active_since)); ?></p>
                                <p class="mb-0" id="duration">Duration: Calculating...</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5>Not Currently On Duty</h5>
                                <p class="mb-0">You can time in anytime to continue your OJT hours.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Days Left</h5>
                        <h2 class="card-text"><?php echo $days_left; ?></h2>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                style="width: <?php echo ($days_completed / 51) * 100; ?>%" 
                                aria-valuenow="<?php echo $days_completed; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="51">
                            </div>
                        </div>
                        <small class="text-muted"><?php echo $days_completed; ?> days completed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Today's Status</h5>
                        <div id="status-display">
                            <?php if (!$timeData): ?>
                                <span class="badge bg-secondary status-badge">Not Started</span>
                            <?php elseif (isset($timeData['time_out'])): ?>
                                <span class="badge bg-info status-badge">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-success status-badge">On Duty</span>
                                <?php if ($current_duration): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Duration: </small>
                                        <span id="duration" class="duration-display"><?php echo $current_duration; ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Hours</h5>
                        <h2 class="card-text total-time">
                            <?php echo $total_hours; ?>h <?php echo $remaining_minutes; ?>m
                        </h2>
                        <p class="text-muted mb-0">Hours accumulated</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="card-title mb-4">Time Tracking</h4>
                        <?php if (!$timeData || isset($timeData['time_out'])): ?>
                            <button id="timeInBtn" class="btn btn-primary time-btn">Time In</button>
                        <?php else: ?>
                            <button id="timeOutBtn" class="btn btn-danger time-btn">Time Out</button>
                        <?php endif; ?>
                        
                        <div id="currentTime" class="mt-3">
                            <h5>Current Time</h5>
                            <div id="clock" class="h3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">Quick Actions</h4>
                        </div>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                            <i class="fas fa-user-edit"></i> Update Profile
                        </button>
                        <a href="narrative_report.php" class="btn btn-primary me-2">
                            <i class="fas fa-file-alt"></i> Make Narrative Report
                        </a>
                        <a href="view_reports.php" class="btn btn-primary">
                            <i class="fas fa-book"></i> View My Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Recent Time Records</h4>
                        <div class="table-responsive">
                            <table class="table table-hover history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = mysqli_fetch_assoc($history_result)): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td><?php echo $record['formatted_time_in']; ?></td>
                                            <td><?php echo $record['formatted_time_out'] ?? 'Active'; ?></td>
                                            <td>
                                                <?php 
                                                    $hours = floor($record['duration_minutes'] / 60);
                                                    $minutes = $record['duration_minutes'] % 60;
                                                    echo "{$hours}h {$minutes}m";
                                                ?>
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

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProfileModalLabel">Update Profile Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="update-profile.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="idnumber" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="idnumber" name="idnumber" 
                                   value="<?php echo htmlspecialchars($userData["idnumber"]); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" 
                                   value="<?php echo htmlspecialchars($userData["firstname"]); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" 
                                   value="<?php echo htmlspecialchars($userData["lastname"]); ?>" required>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <h6 class="mb-3">Change Password (Optional)</h6>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <small class="text-muted">Required only if changing password</small>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila',
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const timeString = now.toLocaleTimeString('en-US', options);
            document.getElementById('clock').textContent = timeString;

            // Update duration if on duty
            const durationElement = document.getElementById('duration');
            if (durationElement) {
                const timeIn = new Date('<?php echo $timeData["time_in"] ?? ""; ?>');
                const diff = now - timeIn;
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                durationElement.textContent = 
                    `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        }

        // Check if there's an active session
        const hasActiveSession = <?php echo $has_active_session ? 'true' : 'false'; ?>;

        // Function to show warning message
        function showTimeoutWarning(event) {
            if (hasActiveSession) {
                const message = "Please time out first before closing the window or logging out!";
                event.preventDefault();
                event.returnValue = message;
                return message;
            }
        }

        // Add event listener for window/tab closing
        window.addEventListener('beforeunload', showTimeoutWarning);

        // Modify logout link behavior
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.querySelector('a[href="logout.php"]');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    if (hasActiveSession) {
                        e.preventDefault();
                        alert("Please time out first before logging out!");
                    }
                });
            }
        });

        setInterval(updateClock, 1000);
        updateClock();

        document.addEventListener('DOMContentLoaded', function() {
            // Time in/out button click handler
            document.getElementById('timeInBtn')?.addEventListener('click', function() {
                fetch('time_tracking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=time_in'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        location.reload();
                    }
                });
            });

            document.getElementById('timeOutBtn')?.addEventListener('click', function() {
                fetch('time_tracking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=time_out'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        location.reload();
                    }
                });
            });

            // Narrative report button click handler
            document.querySelector('a[href="narrative_report.php"]')?.addEventListener('click', function() {
                window.location.href = 'narrative_report.php';
            });

            // View narrative reports button click handler
            document.querySelector('a[href="view_reports.php"]')?.addEventListener('click', function() {
                window.location.href = 'view_reports.php';
            });

            const form = document.querySelector('#updateProfileModal form');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const currentPassword = document.getElementById('current_password');

            form.addEventListener('submit', function(e) {
                // Reset previous error states
                newPassword.classList.remove('is-invalid');
                confirmPassword.classList.remove('is-invalid');
                currentPassword.classList.remove('is-invalid');

                // Check if user is trying to change password
                if (newPassword.value || confirmPassword.value) {
                    // Validate current password is provided
                    if (!currentPassword.value) {
                        e.preventDefault();
                        currentPassword.classList.add('is-invalid');
                        alert('Please enter your current password to change password');
                        return;
                    }

                    // Validate new password length
                    if (newPassword.value.length < 8) {
                        e.preventDefault();
                        newPassword.classList.add('is-invalid');
                        alert('New password must be at least 8 characters long');
                        return;
                    }

                    // Validate password match
                    if (newPassword.value !== confirmPassword.value) {
                        e.preventDefault();
                        newPassword.classList.add('is-invalid');
                        confirmPassword.classList.add('is-invalid');
                        alert('New passwords do not match');
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>