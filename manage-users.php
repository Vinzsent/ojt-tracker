<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

include('config.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - OJT Tracker</title>
    <link href="bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="fontawesome/css/all.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4.2 (Latest Version) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --primary-green: #0B4619;
            --secondary-gold: #FFB800;
            --hover-green: #0d5420;
            --light-green: rgba(11, 70, 25, 0.05);
            --danger: #dc3545;
            --danger-hover: #bb2d3b;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 0.7rem 2rem rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background-color: var(--primary-green);
            color: white;
            border-bottom: none;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.2rem 1.5rem;
        }

        .table-success {
            background-color: var(--primary-green) !important;
            color: white;
        }

        .table-success th {
            border-color: rgba(255, 255, 255, 0.2);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table-hover tbody tr:hover {
            background-color: var(--light-green);
            transition: background-color 0.2s ease;
        }

        .badge {
            padding: 0.6em 1em;
            font-weight: 500;
            border-radius: 6px;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-action {
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border: none;
        }

        .btn-edit {
            background-color: var(--primary-green);
            color: white;
        }

        .btn-edit:hover {
            background-color: var(--hover-green);
            color: white;
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background-color: var(--danger-hover);
            color: white;
            transform: translateY(-2px);
        }

        .btn-back {
            background-color: white;
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background-color: var(--secondary-gold);
            color: var(--primary-green);
            transform: translateY(-2px);
        }

        .btn-print {
            background-color: var(--secondary-gold);
            color: var(--primary-green);
            border: none;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .btn-print:hover {
            background-color: var(--secondary-gold);
            color: var(--primary-green);
            transform: translateY(-2px);
        }

        .page-title {
            color: var(--primary-green);
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            background-color: var(--light-green);
            padding: 12px;
            border-radius: 10px;
            color: var(--primary-green);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            padding: 1.2rem 1.5rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            border: 2px solid #dee2e6;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(11, 70, 25, 0.15);
        }

        .input-group .btn {
            border-radius: 0 8px 8px 0;
            padding: 0.6rem 1rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none;
            }

            .badge {
                border: 1px solid #dee2e6;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="page-title">
            <i class="fas fa-users"></i> Manage Users
        </h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User List</h5>
                <div class="no-print">
                    <button type="button" class="btn btn-back btn-sm me-2" onclick="window.location.href='admin_dashboard.php'">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </button>
                    <button type="button" class="btn btn-print btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Print List
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-success text-dark">
                            <tr>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Password</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users_query = "SELECT * FROM users ORDER BY role, lastname, firstname";
                            $users_result = mysqli_query($connection, $users_query);
                            while ($user = mysqli_fetch_assoc($users_result)):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['idnumber']); ?></td>
                                    <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
                                    <td>
                                        <span class="badge <?php
                                                            echo $user['role'] === 'Admin' ? 'bg-danger' : ($user['role'] === 'Teacher' ? 'bg-danger' : 'bg-success');
                                                            ?>">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['password']); ?></td>
                                    <td>
                                        <span class="badge <?php
                                                            echo $user['status'] === 'active' ? 'bg-success' : ($user['status'] === 'pending' ? 'bg-warning' : 'bg-danger');
                                                            ?>">
                                            <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="no-print">
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action btn-edit"
                                                onclick="editUser(<?php echo $user['user_id']; ?>)"
                                                title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['role'] !== 'Admin'): ?>
                                                <button type="button" class="btn-action btn-delete"
                                                    onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>')"
                                                    title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUserForm" action="update-user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">ID Number</label>
                            <input type="text" class="form-control" name="idnumber" id="edit_idnumber" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname" id="edit_firstname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastname" id="edit_lastname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="password" id="edit_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="delete-user.php" method="POST" class="d-inline">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            fetch('get-user.php?id=' + userId)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('edit_user_id').value = user.user_id;
                    document.getElementById('edit_idnumber').value = user.idnumber;
                    document.getElementById('edit_firstname').value = user.firstname;
                    document.getElementById('edit_lastname').value = user.lastname;
                    document.getElementById('edit_password').value = user.password;
                    document.getElementById('edit_status').value = user.status;

                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                });
        }

        function confirmDelete(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }

        function togglePassword() {
            const passwordInput = document.getElementById('edit_password');
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
        }
    </script>
</body>

</html>