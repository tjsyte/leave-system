<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db_connect.php';
check_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Leave Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header text-center">
            <h3 class="mb-1"><i class="fas fa-calendar-check me-2"></i>LeaveMS</h3>
            <p class="mb-0 small opacity-75">Leave Management System</p>
        </div>
        <div class="sidebar-menu">
            <ul class="list-unstyled">
                <?php if ($_SESSION['user_role'] === 'manager'): ?>
                    <li class="mb-1">
                        <a href="../manager/dashboard.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'manager') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt me-3"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../manager/all_leaves.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'all_leaves.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list me-3"></i>
                            <span>All Leave Requests</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../manager/pending_approvals.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'pending_approvals.php') ? 'active' : ''; ?>">
                            <i class="fas fa-clock me-3"></i>
                            <span>Pending Approvals</span>
                            <?php
                            $pending_query = "SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'";
                            $pending_result = mysqli_query($conn, $pending_query);
                            $pending_count = mysqli_fetch_assoc($pending_result)['count'];
                            if ($pending_count > 0):
                            ?>
                                <span class="badge bg-warning text-dark ms-auto"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../manager/employees.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'employees.php') ? 'active' : ''; ?>">
                            <i class="fas fa-users me-3"></i>
                            <span>Employees</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../manager/reports.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar me-3"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../manager/profile.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                            <i class="fas fa-user-cog me-3"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="mb-1">
                        <a href="../employee/dashboard.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'employee') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt me-3"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../employee/file_leave.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'file_leave.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle me-3"></i>
                            <span>File Leave Request</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../employee/my_leaves.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'my_leaves.php') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt me-3"></i>
                            <span>My Leave Requests</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../employee/leave_balance.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'leave_balance.php') ? 'active' : ''; ?>">
                            <i class="fas fa-balance-scale me-3"></i>
                            <span>Leave Balance</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="../employee/profile.php" class="d-flex align-items-center text-decoration-none <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                            <i class="fas fa-user-cog me-3"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="../auth/logout.php" class="d-flex align-items-center text-decoration-none">
                        <i class="fas fa-sign-out-alt me-3"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header with Bootstrap Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom top-header">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary d-lg-none me-3" id="sidebarToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="navbar-brand mb-0 h4 fw-bold"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                </div>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar me-2">
                                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                            </div>
                            <div class="d-none d-md-block text-start">
                                <div class="fw-semibold small"><?php echo $_SESSION['full_name']; ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?php echo ucfirst($_SESSION['user_role']); ?> • <?php echo $_SESSION['department']; ?>
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header">Account</h6></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $_SESSION['user_role'] === 'manager' ? '../manager/profile.php' : '../employee/profile.php'; ?>">
                                    <i class="fas fa-user-cog me-2"></i>My Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Content Area with Bootstrap Container -->
        <div class="content-area">
            <div class="container-fluid">
