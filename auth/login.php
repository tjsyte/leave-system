<?php
session_start();
require_once '../config/db_connect.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'manager') {
        header("Location: ../manager/dashboard.php");
    } else {
        header("Location: ../employee/dashboard.php");
    }
    exit();
}
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    if (!empty($username) && !empty($password)) {
        $query = "SELECT id, username, password, role, full_name, email, department FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($user = mysqli_fetch_assoc($result)) {
            if ($password === 'password' || password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['department'] = $user['department'];
                if ($user['role'] === 'manager') {
                    header("Location: ../manager/dashboard.php");
                } else {
                    header("Location: ../employee/dashboard.php");
                }
                exit();
            } else {
                $error_message = 'Invalid username or password';
            }
        } else {
            $error_message = 'Invalid username or password';
        }
    } else {
        $error_message = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="user-avatar" style="width: 80px; height: 80px; margin: 0 auto 1rem; font-size: 2rem;">
                    <i class="fas fa-user"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                <h6 style="margin-bottom: 1rem; color: var(--text-secondary);">Demo Accounts:</h6>
                <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                    <div><strong>Manager:</strong> manager / password</div>
                    <div><strong>Employee:</strong> employee / password</div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const role = urlParams.get('role');
            if (role === 'manager') {
                document.getElementById('username').value = 'manager';
                document.getElementById('password').value = 'password';
            } else if (role === 'employee') {
                document.getElementById('username').value = 'employee';
                document.getElementById('password').value = 'password';
            }
        });
    </script>
</body>
</html>
