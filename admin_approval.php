<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

include('config.php');

// Handle teacher approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'active' : 'rejected';
    
    // Make sure we're only updating teacher accounts
    $stmt = $connection->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'Teacher'");
    $stmt->bind_param("si", $status, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = ($action === 'approve') ? 
            "Teacher account has been approved successfully!" : 
            "Teacher account has been rejected.";
        $_SESSION['message_type'] = ($action === 'approve') ? 'success' : 'danger';
    } else {
        $_SESSION['message'] = "Error processing the request.";
        $_SESSION['message_type'] = 'danger';
    }
    
    $stmt->close();
    header("Location: admin_approval.php");
    exit;
}

// Get pending teacher accounts only
$stmt = $connection->prepare("SELECT user_id, firstname, lastname, idnumber, gender, created_at FROM users WHERE role = 'Teacher' AND status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Teacher Approval</title>
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
            padding: 2rem;
        }

        .admin-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .admin-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--dcc-gold);
        }

        .page-title {
            color: var(--dcc-green);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title i {
            color: var(--dcc-gold);
        }

        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 2rem;
        }

        .table thead {
            background: var(--dcc-green);
            color: white;
        }

        .table th {
            font-weight: 600;
            padding: 1rem;
            border: none;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .btn-approve, .btn-reject {
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-approve {
            background-color: var(--dcc-green);
            color: white;
            border: none;
        }

        .btn-approve:hover {
            background-color: #083714;
            color: white;
            transform: translateY(-2px);
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-reject:hover {
            background-color: #bb2d3b;
            color: white;
            transform: translateY(-2px);
        }

        .back-btn {
            background: var(--dcc-green);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: #083714;
            color: white;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--dcc-gold);
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-container">
            <h1 class="page-title">
                <i class="fas fa-chalkboard-teacher"></i>
                Teacher Account Approval
            </h1>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['idnumber']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-pending">Pending</span>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-approve btn-sm" onclick="return confirm('Are you sure you want to approve this teacher account?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-reject btn-sm" onclick="return confirm('Are you sure you want to reject this teacher account?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>No Pending Approvals</h3>
                    <p>There are no teacher accounts waiting for approval at this time.</p>
                </div>
            <?php endif; ?>
            
            <a href="admin_dashboard.php" class="back-btn mt-4">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
