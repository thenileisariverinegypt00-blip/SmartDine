<?php
session_start();
include 'db_config.php';

$error = '';
$session_expired = isset($_GET['session_expired']) ? true : false;

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin_dashboard.php');
    } elseif ($_SESSION['role'] == 'customer') {
        header('Location: customer_dashboard.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember']) ? true : false;

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['remember_me'] = $remember_me;
                
                // Set session cookie with extended lifetime if remember me is checked
                if ($remember_me) {
                    setcookie(session_name(), session_id(), time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header('Location: admin_dashboard.php');
                } elseif ($user['role'] == 'customer') {
                    header('Location: customer_dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Dine - Restaurant Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #d4a574;
            --dark-color: #8b4513;
            --light-color: #fff8dc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="8" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="12" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="50" r="10" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 6s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 10;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .login-header p {
            font-size: 1rem;
            margin: 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-control {
            height: 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }

        .form-control::placeholder {
            color: #999;
        }

        .input-group-text {
            background-color: transparent;
            border: 2px solid #e0e0e0;
            border-right: none;
            color: var(--dark-color);
        }

        .form-control.form-control-password {
            border-left: none;
        }

        .toggle-password {
            cursor: pointer;
            color: var(--primary-color);
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--dark-color);
        }

        .btn-login {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(212, 165, 116, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 165, 116, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-custom {
            border: none;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffeaa7;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #999;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: #e0e0e0;
        }

        .divider span {
            margin: 0 15px;
            font-size: 0.9rem;
        }

        .login-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .login-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .login-links a:hover {
            color: var(--dark-color);
            text-decoration: underline;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .checkbox-group label {
            margin: 0 0 0 10px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
        }

        .demo-credentials {
            background-color: var(--light-color);
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .demo-credentials h6 {
            color: var(--dark-color);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .demo-role {
            margin-bottom: 8px;
            color: #333;
        }

        .demo-role strong {
            color: var(--dark-color);
        }

        .demo-role code {
            background-color: white;
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--primary-color);
            font-family: 'Courier New', monospace;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                max-width: 100%;
            }

            .login-header h1 {
                font-size: 2rem;
            }

            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <h1>🍽️ Smart Dine</h1>
                <p>Restaurant Management System</p>
            </div>

            <!-- Body -->
            <div class="login-body">
                <?php if ($session_expired): ?>
                    <div class="alert alert-warning alert-custom" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Session Expired:</strong> Your session has expired due to inactivity. Please log in again.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-custom" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">
                            <i class="bi bi-person"></i> Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autofocus>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control form-control-password" id="password" name="password" placeholder="Enter your password" required>
                            <span class="input-group-text">
                                <i class="bi bi-eye toggle-password" id="togglePassword" onclick="togglePasswordVisibility()"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me for 30 days</label>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>

                <!-- Divider -->
                <div class="divider">
                    <span>Don't have an account?</span>
                </div>

                <!-- Register Link -->
                <div class="login-links">
                    <a href="register.php">
                        <i class="bi bi-person-plus"></i> Create Account
                    </a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#demoModal">
                        <i class="bi bi-info-circle"></i> Demo Info
                    </a>
                </div>

                <!-- Demo Credentials -->
                <div class="demo-credentials">
                    <h6><i class="bi bi-star-fill"></i> Demo Credentials</h6>
                    <div class="demo-role">
                        <strong>Admin:</strong> <code>admin</code> / <code>admin123</code>
                    </div>
                    <div class="demo-role">
                        <strong>Customer:</strong> <code>customer</code> / <code>customer123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Info Modal -->
    <div class="modal fade" id="demoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle"></i> System Access Levels
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-danger">
                            <i class="bi bi-shield-lock"></i> Admin Role
                        </h6>
                        <p class="text-muted">Full access to the system. Manage menu, orders, tables, and customer accounts.</p>
                        <small><strong>Demo:</strong> admin / admin123</small>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-success">
                            <i class="bi bi-person"></i> Customer Role
                        </h6>
                        <p class="text-muted">Can browse menu, place orders, and track order status in real-time.</p>
                        <small><strong>Demo:</strong> customer / customer123</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Remember me functionality
        const rememberCheckbox = document.getElementById('remember');
        const usernameInput = document.getElementById('username');

        // Load saved username if exists
        if (localStorage.getItem('saved_username')) {
            usernameInput.value = localStorage.getItem('saved_username');
            rememberCheckbox.checked = true;
        }

        // Save username on login
        document.querySelector('form').addEventListener('submit', function() {
            if (rememberCheckbox.checked) {
                localStorage.setItem('saved_username', usernameInput.value);
            } else {
                localStorage.removeItem('saved_username');
            }
        });
    </script>
</body>
</html>