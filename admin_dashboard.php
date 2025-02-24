<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

include('config.php');

// Get statistics
$stats = [];

// Total Users
$stmt = $connection->query("SELECT COUNT(*) as total FROM users WHERE role != 'Admin'");
$stats['total_users'] = $stmt->fetch_assoc()['total'];

// Total Teachers
$stmt = $connection->query("SELECT COUNT(*) as total FROM users WHERE role = 'Teacher'");
$stats['total_teachers'] = $stmt->fetch_assoc()['total'];

// Total Students
$stmt = $connection->query("SELECT COUNT(*) as total FROM users WHERE role = 'Student'");
$stats['total_students'] = $stmt->fetch_assoc()['total'];

// Pending Teacher Approvals
$stmt = $connection->query("SELECT COUNT(*) as total FROM users WHERE role = 'Teacher' AND status = 'pending'");
$stats['pending_teacher_approvals'] = $stmt->fetch_assoc()['total'];

// Pending Student Approvals
$stmt = $connection->query("SELECT COUNT(*) as total FROM users WHERE role = 'Student' AND status = 'pending'");
$stats['pending_student_approvals'] = $stmt->fetch_assoc()['total'];

// Recent Registrations
$stmt = $connection->query("SELECT firstname, lastname, role, status, created_at FROM users 
                          WHERE role != 'Admin' 
                          ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetch_all(MYSQLI_ASSOC);

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OJT Tracker</title>
    <link href="bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --dcc-green: #0B4619;
            --dcc-gold: #FFB800;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--dcc-green) 0%, #083714 100%);
            min-height: 100vh;
        }

        .sidebar {
            background: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 250px;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 2px solid #eee;
            text-align: center;
        }

        .sidebar-header h3 {
            color: var(--dcc-green);
            font-weight: 700;
            margin: 0;
        }

        .nav-link {
            padding: 1rem 1.5rem;
            color: #555;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--dcc-green);
            color: white;
        }

        .nav-link i {
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .page-title {
            color: white;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .stat-icon.users {
            background: rgba(11, 70, 25, 0.1);
            color: var(--dcc-green);
        }

        .stat-icon.teachers {
            background: rgba(255, 184, 0, 0.1);
            color: var(--dcc-gold);
        }

        .stat-icon.students {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .stat-icon.pending {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            margin: 0;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .content-card h3 {
            color: var(--dcc-green);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table th {
            font-weight: 600;
            color: var(--dcc-green);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 500;
        }

        .badge-pending {
            background-color: var(--dcc-gold);
            color: var(--dcc-green);
        }

        .badge-active {
            background-color: var(--dcc-green);
            color: white;
        }

        .user-info {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            color: #666;
        }

        .logout-btn {
            margin-top: auto;
            padding: 1rem 1.5rem;
            color: #dc3545;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #dc3545;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>OJT TRACKER</h3>
        </div>
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?>
        </div>
        <nav class="mt-3">
            <a href="admin_dashboard.php" class="nav-link active">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="admin_approval.php" class="nav-link">
                <i class="fas fa-user-check"></i> Teacher Approvals
                <?php if ($stats['pending_teacher_approvals'] > 0): ?>
                    <span class="badge badge-pending ms-auto"><?php echo $stats['pending_teacher_approvals']; ?></span>
                <?php endif; ?>
            </a>
            <a href="student_approval.php" class="nav-link">
                <i class="fas fa-user-graduate"></i> Student Approvals
                <?php if ($stats['pending_student_approvals'] > 0): ?>
                    <span class="badge badge-pending ms-auto"><?php echo $stats['pending_student_approvals']; ?></span>
                <?php endif; ?>
            </a>
            <a href="manage-users.php" class="nav-link">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="logout.php" class="nav-link logout-btn mt-auto">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Dashboard Overview</h1>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users fa-lg"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <p class="stat-label">Total Users</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon teachers">
                    <i class="fas fa-chalkboard-teacher fa-lg"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_teachers']; ?></div>
                <p class="stat-label">Teachers</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon students">
                    <i class="fas fa-user-graduate fa-lg"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                <p class="stat-label">Students</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock fa-lg"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending_teacher_approvals'] + $stats['pending_student_approvals']; ?></div>
                <p class="stat-label">Total Pending Approvals</p>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="content-card">
            <h3>
                <i class="fas fa-history"></i>
                Recent Registrations
            </h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['status'] === 'active' ? 'badge-active' : 'badge-pending'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
