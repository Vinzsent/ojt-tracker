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

// Handle search and filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Build the query based on filters
$where_clause = "WHERE u.role = 'Student'";
if (!empty($search)) {
    $where_clause .= " AND (
        u.firstname LIKE '%$search%' OR 
        u.lastname LIKE '%$search%' OR 
        u.idnumber LIKE '%$search%'
    )";
}

if ($status_filter === 'active') {
    $where_clause .= " AND EXISTS (
        SELECT 1 FROM time_records tr2 
        WHERE tr2.user_id = u.user_id 
        AND tr2.time_out IS NULL
    )";
} elseif ($status_filter === 'inactive') {
    $where_clause .= " AND NOT EXISTS (
        SELECT 1 FROM time_records tr2 
        WHERE tr2.user_id = u.user_id 
        AND tr2.time_out IS NULL
    )";
}

// Add sorting
$order_by = "ORDER BY ";
switch ($sort) {
    case 'name_asc':
        $order_by .= "u.lastname ASC, u.firstname ASC";
        break;
    case 'name_desc':
        $order_by .= "u.lastname DESC, u.firstname DESC";
        break;
    case 'hours_asc':
        $order_by .= "total_hours ASC";
        break;
    case 'hours_desc':
        $order_by .= "total_hours DESC";
        break;
    case 'progress_asc':
        $order_by .= "days_completed ASC";
        break;
    case 'progress_desc':
        $order_by .= "days_completed DESC";
        break;
    default:
        $order_by .= "u.lastname ASC, u.firstname ASC";
}

// Get all students with their total hours
$students_query = "
    SELECT 
        u.user_id,
        u.firstname,
        u.lastname,
        u.idnumber,
        u.gender,
        u.status,
        COALESCE(
            SUM(TIMESTAMPDIFF(MINUTE, tr.time_in, IFNULL(tr.time_out, NOW()))) / 60, 
            0
        ) as total_hours,
        COUNT(DISTINCT DATE(tr.time_in)) as days_completed,
        MAX(tr.date) as last_active_date,
        (
            SELECT time_in 
            FROM time_records 
            WHERE user_id = u.user_id 
            AND time_out IS NULL 
            ORDER BY time_in DESC 
            LIMIT 1
        ) as current_session,
        (
            SELECT COUNT(*) 
            FROM time_records 
            WHERE user_id = u.user_id 
            AND DATE(time_in) = CURRENT_DATE
        ) as today_sessions
    FROM users u
    LEFT JOIN time_records tr ON u.user_id = tr.user_id
    $where_clause
    GROUP BY u.user_id
    $order_by";

$result = mysqli_query($connection, $students_query);
$students = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate progress percentage for each student
$required_hours = 408; // 51 days × 8 hours

// Get total counts for summary
$total_students = count($students);
$active_now = 0;
$completed_today = 0;
foreach ($students as $student) {
    if (!is_null($student['current_session'])) $active_now++;
    if ($student['today_sessions'] > 0) $completed_today++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - Teacher Portal</title>
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

        .table th {
            background-color: var(--dcc-green);
            color: white;
            border: none;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
        }

        .progress-bar {
            background-color: var(--dcc-green);
        }

        .student-card {
            transition: transform 0.2s;
        }

        .student-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .filter-card {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .search-input {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
        }

        .search-input:focus {
            border-color: var(--dcc-green);
            box-shadow: none;
        }

        .filter-btn {
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
            background-color: #e9ecef;
            border: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background-color: var(--dcc-green);
            color: white;
        }

        .sort-dropdown {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
        }

        .summary-card {
            background-color: var(--dcc-green);
            color: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .summary-number {
            font-size: 2rem;
            font-weight: bold;
        }

        .student-details {
            display: none;
            background-color: rgba(0, 0, 0, 0.8);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }

        .details-content {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4 no-print">
        <div class="container">
            <a class="navbar-brand" href="#">OJT Tracker - Teacher Portal</a>
            <div class="d-flex">
                <a href="teacher-dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-number"><?php echo $total_students; ?></div>
                    <div>Total Students</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-number"><?php echo $active_now; ?></div>
                    <div>Currently Active</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-number"><?php echo $completed_today; ?></div>
                    <div>Completed Sessions Today</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card no-print">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control search-input" 
                           placeholder="Search by name or ID..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <div class="btn-group" role="group">
                        <a href="?status=all<?php echo !empty($search) ? '&search='.$search : ''; ?>" 
                           class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?status=active<?php echo !empty($search) ? '&search='.$search : ''; ?>" 
                           class="filter-btn <?php echo $status_filter === 'active' ? 'active' : ''; ?>">Active Now</a>
                        <a href="?status=inactive<?php echo !empty($search) ? '&search='.$search : ''; ?>" 
                           class="filter-btn <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>">Inactive</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select sort-dropdown" onchange="this.form.submit()">
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="hours_asc" <?php echo $sort === 'hours_asc' ? 'selected' : ''; ?>>Hours (Low-High)</option>
                        <option value="hours_desc" <?php echo $sort === 'hours_desc' ? 'selected' : ''; ?>>Hours (High-Low)</option>
                        <option value="progress_asc" <?php echo $sort === 'progress_asc' ? 'selected' : ''; ?>>Progress (Low-High)</option>
                        <option value="progress_desc" <?php echo $sort === 'progress_desc' ? 'selected' : ''; ?>>Progress (High-Low)</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-success w-100" onclick="window.print()">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="row">
            <?php foreach ($students as $student): 
                $progress = min(100, ($student['total_hours'] / $required_hours) * 100);
                $is_currently_active = !is_null($student['current_session']);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card student-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="student-name mb-1">
                                        <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
                                    </h5>
                                    <div class="student-info">
                                        <div>ID: <?php echo htmlspecialchars($student['idnumber']); ?></div>
                                        <div>Gender: <?php echo htmlspecialchars($student['gender']); ?></div>
                                    </div>
                                </div>
                                <span class="status-badge <?php echo $is_currently_active ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $is_currently_active ? 'Currently Active' : 'Inactive'; ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Progress</span>
                                    <span class="hours-completed">
                                        <?php echo number_format($student['total_hours'], 1); ?> / <?php echo $required_hours; ?> hours
                                    </span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $progress; ?>%" 
                                         aria-valuenow="<?php echo $progress; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between student-info">
                                <div>
                                    <i class="fas fa-calendar-check me-1"></i>
                                    <?php echo $student['days_completed']; ?> days completed
                                </div>
                                <div>
                                    <i class="fas fa-clock me-1"></i>
                                    <?php 
                                        echo $student['last_active_date'] 
                                            ? 'Last active: ' . date('M d, Y', strtotime($student['last_active_date']))
                                            : 'Not started yet';
                                    ?>
                                </div>
                            </div>

                            <div class="mt-3 no-print">
                                <button class="btn btn-sm btn-outline-success w-100" 
                                        onclick="viewStudentDetails(<?php echo $student['user_id']; ?>)">
                                    <i class="fas fa-chart-line me-1"></i>View Detailed Progress
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (mysqli_num_rows($result) === 0): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4>No Students Found</h4>
                    <p class="text-muted">
                        <?php 
                        if (!empty($search)) {
                            echo "No students match your search criteria.";
                        } else {
                            echo "There are currently no students registered in the system.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Student Details Modal -->
        <div id="studentDetailsModal" class="student-details">
            <div class="details-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Student Progress Details</h4>
                    <button type="button" class="btn-close" onclick="closeStudentDetails()"></button>
                </div>
                <div id="studentDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewStudentDetails(userId) {
            // Show modal
            document.getElementById('studentDetailsModal').style.display = 'block';
            
            // Load student details via AJAX
            fetch('get-student-details.php?user_id=' + userId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('studentDetailsContent').innerHTML = html;
                });
        }

        function closeStudentDetails() {
            document.getElementById('studentDetailsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('studentDetailsModal')) {
                closeStudentDetails();
            }
        }
    </script>
</body>
</html>
