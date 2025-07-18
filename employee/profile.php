<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/table_component.php';
check_login();
$page_title = "My Profile";
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $department = sanitize_input($_POST['department']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if (!empty($full_name) && !empty($email) && !empty($department)) {
        $query = "UPDATE users SET full_name = ?, email = ?, department = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $department, $_SESSION['user_id']);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['department'] = $department;
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $pwd_query = "UPDATE users SET password = ? WHERE id = ?";
                    $pwd_stmt = mysqli_prepare($conn, $pwd_query);
                    mysqli_stmt_bind_param($pwd_stmt, "si", $hashed_password, $_SESSION['user_id']);
                    mysqli_stmt_execute($pwd_stmt);
                    $success_message = "Profile and password updated successfully!";
                } else {
                    $error_message = "New passwords do not match!";
                }
            } else {
                $success_message = "Profile updated successfully!";
            }
        } else {
            $error_message = "Error updating profile: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Please fill in all required fields!";
    }
}
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
include '../includes/header.php';
?>
<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">My Profile</h1>
            <p class="text-muted">Manage your personal information and account settings</p>
        </div>
    </div>
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h5>
                    <p class="text-muted"><?php echo ucfirst($_SESSION['user_role']); ?></p>
                    <p class="text-muted"><?php echo htmlspecialchars($_SESSION['department']); ?></p>
                    <div class="mt-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border-end">
                                    <?php
                                    $leave_count_query = "SELECT COUNT(*) as total FROM leave_requests WHERE user_id = ?";
                                    $stmt = mysqli_prepare($conn, $leave_count_query);
                                    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                                    mysqli_stmt_execute($stmt);
                                    $leave_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
                                    ?>
                                    <h6 class="mb-0"><?php echo $leave_count; ?></h6>
                                    <small class="text-muted">Total Requests</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <?php
                                    $approved_count_query = "SELECT COUNT(*) as approved FROM leave_requests WHERE user_id = ? AND status = 'approved'";
                                    $stmt = mysqli_prepare($conn, $approved_count_query);
                                    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                                    mysqli_stmt_execute($stmt);
                                    $approved_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['approved'];
                                    ?>
                                    <h6 class="mb-0"><?php echo $approved_count; ?></h6>
                                    <small class="text-muted">Approved</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <?php
                                $balance_query = "SELECT SUM(balance) as total_balance FROM leave_balances WHERE user_id = ?";
                                $stmt = mysqli_prepare($conn, $balance_query);
                                mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                                mysqli_stmt_execute($stmt);
                                $total_balance = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_balance'] ?? 0;
                                ?>
                                <h6 class="mb-0"><?php echo $total_balance; ?></h6>
                                <small class="text-muted">Days Left</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        Edit Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" 
                                           value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" 
                                           value="<?php echo ucfirst($user_data['role']); ?>" disabled>
                                    <small class="text-muted">Role cannot be changed</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department *</label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="Human Resources" <?php echo $user_data['department'] === 'Human Resources' ? 'selected' : ''; ?>>Human Resources</option>
                                        <option value="IT Department" <?php echo $user_data['department'] === 'IT Department' ? 'selected' : ''; ?>>IT Department</option>
                                        <option value="Finance" <?php echo $user_data['department'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                        <option value="Marketing" <?php echo $user_data['department'] === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Sales" <?php echo $user_data['department'] === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="Operations" <?php echo $user_data['department'] === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                        <option value="Customer Service" <?php echo $user_data['department'] === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        <h6 class="mb-3">
                            <i class="fas fa-lock me-2"></i>
                            Change Password (Optional)
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const currentPassword = document.getElementById('current_password');
    function validatePasswords() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        if (newPassword.value && !currentPassword.value) {
            currentPassword.setCustomValidity('Current password is required to change password');
        } else {
            currentPassword.setCustomValidity('');
        }
    }
    newPassword.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
    currentPassword.addEventListener('input', validatePasswords);
});
</script>
<?php include '../includes/footer.php'; ?>
