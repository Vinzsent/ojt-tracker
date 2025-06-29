<!DOCTYPE html>
<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="bootstrap-5.1.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>OJT TRACKER</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            margin: 2rem 0;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--dcc-gold);
        }

        .logo-title {
            color: var(--dcc-green);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .form-control {
            border: 2px solid #eee;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--dcc-green);
            box-shadow: 0 0 0 0.2rem rgba(11, 70, 25, 0.1);
        }

        .form-label {
            color: #555;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background-color: var(--dcc-green);
            border: none;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #083714;
            transform: translateY(-2px);
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            padding: 0;
        }

        .login-link {
            margin-top: 1.5rem;
            color: #666;
            font-size: 1.1rem;
        }

        .login-link a {
            color: var(--dcc-green);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            color: var(--dcc-gold);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1 class="logo-title text-center">OJT TRACKER</h1>
        <p class="subtitle text-center">Please register to continue</p>
        
        <form action="register-backend.php" method="post">
            <input type="hidden" name="created_at" value="<?php echo date('Y-m-d H:i:s'); ?>">
            
            <div class="mb-3">
                <label for="firstname" class="form-label">First Name</label>
                <input type="text" name="firstname" id="firstname" placeholder="Enter your first name" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label for="lastname" class="form-label">Last Name</label>
                <input type="text" name="lastname" id="lastname" placeholder="Enter your last name" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label for="idnumber" class="form-label">ID Number</label>
                <input type="text" name="idnumber" id="idnumber" placeholder="Enter your ID number" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label required>
                <select name="gender" id="gender" class="form-control">
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="roleStudent" value="Student" checked>
                        <label class="form-check-label" for="roleStudent">
                            Student
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="roleTeacher" value="Teacher">
                        <label class="form-check-label" for="roleTeacher">
                            Teacher
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label required>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Enter your password" class="form-control">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Register</button>
            
            <div class="login-link text-center">
                Already have an account? <a href="index.php">Login</a>
            </div>
        </form>
    </div>

    <script src="bootstrap-5.1.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            var passwordInput = document.getElementById("password");
            var eyeIcon = document.getElementById("eyeIcon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }

        // Add event listener for role radio buttons
        document.getElementById('roleTeacher').addEventListener('change', function() {
            if (this.checked) {
                alert('Please note: Teacher accounts require admin approval before activation. You will be notified once your account is approved.');
            }
        });

        // Form submission handler
        document.querySelector('form').addEventListener('submit', function(e) {
            if (document.getElementById('roleTeacher').checked) {
                const message = 'Your teacher account will be pending admin approval after registration. Continue?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>