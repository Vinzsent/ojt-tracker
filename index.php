<!DOCTYPE html>
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

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
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

        .register-link {
            margin-top: 1.5rem;
            color: #666;
        }

        .register-link a {
            color: var(--dcc-green);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            color: var(--dcc-gold);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="logo-title text-center">OJT TRACKER</h1>
        <p class="subtitle text-center">Please login to continue</p>
        
        <form action="login-backend.php" method="post">
            <input type="hidden" name="status" value="online">
            
            <div class="mb-3">
                <label for="idnumber" class="form-label">ID Number</label>
                <input type="text" name="idnumber" id="idnumber" placeholder="Enter your ID number" class="form-control">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Enter your password" class="form-control">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i id="eyeIcon" class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Login</button>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register</a>
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
    </script>
</body>
</html>