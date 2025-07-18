<?php
$page_title = "Manager Dashboard";
include '../includes/header.php';
require_once '../config/db_connect.php';
check_login();
check_manager();
$current_date = date('Y-m-d');
$current_month = date('Y-m');
$stats_query = "SELECT 
    COUNT(DISTINCT u.id) as total_employees,
    COUNT(DISTINCT u.department) as total_departments,
    COUNT(lr.id) as total_requests,
    SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as total_days_approved
FROM users u
LEFT JOIN leave_requests lr ON u.id = lr.user_id
WHERE u.role = 'employee'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
$recent_query = "SELECT lr.id, u.full_name, u.department, lr.leave_type, lr.date_from, lr.date_to, 
                 lr.days_requested, lr.status, lr.filed_date, lr.reason
                 FROM leave_requests lr
                 JOIN users u ON lr.user_id = u.id
                 ORDER BY lr.filed_date DESC
                 LIMIT 8";
$recent_result = mysqli_query($conn, $recent_query);
$dept_query = "SELECT 
    u.department,
    COUNT(u.id) as employee_count,
    COUNT(lr.id) as total_requests,
    SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests
FROM users u
LEFT JOIN leave_requests lr ON u.id = lr.user_id
WHERE u.role = 'employee'
GROUP BY u.department
ORDER BY employee_count DESC
LIMIT 4";
$dept_result = mysqli_query($conn, $dept_query);
$urgent_query = "SELECT lr.id, u.full_name, u.department, lr.leave_type, lr.date_from, lr.filed_date,
                 DATEDIFF(lr.date_from, CURDATE()) as days_until_leave
                 FROM leave_requests lr
                 JOIN users u ON lr.user_id = u.id
                 WHERE lr.status = 'pending' 
                 AND lr.date_from <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY lr.date_from ASC
                 LIMIT 5";
$urgent_result = mysqli_query($conn, $urgent_query);
?>
<!-- Welcome Section -->
<div class="dashboard-overview">
    <div class="welcome-content">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-3">
                    <div class="user-avatar me-3" style="width: 70px; height: 70px; font-size: 1.75rem;">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                        <p class="mb-0">Here's what's happening with your team today. You have <?php echo $stats['pending_requests']; ?> requests awaiting your review.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div style="opacity: 0.9;">
                    <i class="fas fa-calendar-day me-2"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem; margin-top: 0.5rem;">
                    <i class="fas fa-building me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['department']); ?> Department
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Statistics Cards -->
<div class="dashboard-stats">
    <div class="dashboard-stat-card">
        <div class="stat-content">
            <div class="stat-info">
                <h3><?php echo $stats['total_employees']; ?></h3>
                <p>Total Employees</p>
            </div>
            <div class="stat-icon-large primary">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="progress-bar mt-3">
            <div class="progress-fill" style="width: 100%; background: linear-gradient(90deg, var(--primary-color), var(--accent-color));"></div>
        </div>
    </div>
    <div class="dashboard-stat-card warning">
        <div class="stat-content">
            <div class="stat-info">
                <h3><?php echo $stats['pending_requests']; ?></h3>
                <p>Pending Requests</p>
            </div>
            <div class="stat-icon-large warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="progress-bar mt-3">
            <div class="progress-fill" style="width: <?php echo $stats['total_requests'] > 0 ? ($stats['pending_requests'] / $stats['total_requests']) * 100 : 0; ?>%; background: linear-gradient(90deg, var(--warning-color), #d97706);"></div>
        </div>
    </div>
    <div class="dashboard-stat-card success">
        <div class="stat-content">
            <div class="stat-info">
                <h3><?php echo $stats['approved_requests']; ?></h3>
                <p>Approved Requests</p>
            </div>
            <div class="stat-icon-large success">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="progress-bar mt-3">
            <div class="progress-fill" style="width: <?php echo $stats['total_requests'] > 0 ? ($stats['approved_requests'] / $stats['total_requests']) * 100 : 0; ?>%; background: linear-gradient(90deg, var(--success-color), #059669);"></div>
        </div>
    </div>
    <div class="dashboard-stat-card danger">
        <div class="stat-content">
            <div class="stat-info">
                <h3><?php echo $stats['total_days_approved']; ?></h3>
                <p>Days Approved</p>
            </div>
            <div class="stat-icon-large danger">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
        <div class="progress-bar mt-3">
            <div class="progress-fill" style="width: 85%; background: linear-gradient(90deg, var(--accent-color), #0891b2);"></div>
        </div>
    </div>
</div>
<!-- Quick Actions -->
<div class="dashboard-actions">
    <a href="pending_approvals.php" class="dashboard-action-card">
        <div class="action-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="action-content">
            <h6>Review Pending Approvals</h6>
            <p><?php echo $stats['pending_requests']; ?> requests need your attention</p>
        </div>
    </a>
    <a href="all_leaves.php" class="dashboard-action-card">
        <div class="action-icon">
            <i class="fas fa-list"></i>
        </div>
        <div class="action-content">
            <h6>View All Leave Requests</h6>
            <p>Browse all <?php echo $stats['total_requests']; ?> leave requests</p>
        </div>
    </a>
    <a href="employees.php" class="dashboard-action-card">
        <div class="action-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="action-content">
            <h6>Manage Employees</h6>
            <p>Oversee <?php echo $stats['total_employees']; ?> team members</p>
        </div>
    </a>
    <a href="reports.php" class="dashboard-action-card">
        <div class="action-icon">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="action-content">
            <h6>Generate Reports</h6>
            <p>Create detailed analytics reports</p>
        </div>
    </a>
</div>
<!-- Main Dashboard Grid -->
<div class="row">
    <!-- Left Column - Recent Activity & Urgent Items -->
    <div class="col-lg-8">
        <!-- Urgent Requests Alert -->
        <?php if (mysqli_num_rows($urgent_result) > 0): ?>
        <div class="card mb-4" style="border-left: 4px solid var(--warning-color);">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="card-title mb-0 text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Urgent Requests - Need Immediate Attention
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while ($urgent = mysqli_fetch_assoc($urgent_result)): ?>
                    <div class="col-md-6 mb-3">
                        <div class="p-3 border rounded bg-light">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong class="text-primary"><?php echo htmlspecialchars($urgent['full_name']); ?></strong>
                                <span class="badge bg-warning text-dark">
                                    <?php echo $urgent['days_until_leave']; ?> days
                                </span>
                            </div>
                            <div class="small text-muted mb-2">
                                <?php echo htmlspecialchars($urgent['department']); ?> • <?php echo htmlspecialchars($urgent['leave_type']); ?>
                            </div>
                            <div class="small">
                                <strong>Leave Date:</strong> <?php echo date('M j, Y', strtotime($urgent['date_from'])); ?>
                            </div>
                            <div class="mt-2">
                                <a href="pending_approvals.php" class="btn btn-sm btn-warning">
                                    <i class="fas fa-eye me-1"></i>Review
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- Recent Activity -->
        <div class="recent-activity-card">
            <div class="recent-activity-header">
                <h5><i class="fas fa-history me-2"></i>Recent Leave Requests</h5>
            </div>
            <div class="activity-list">
                <?php if (mysqli_num_rows($recent_result) > 0): ?>
                    <?php while ($request = mysqli_fetch_assoc($recent_result)): ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <div class="activity-info">
                                <h6><?php echo htmlspecialchars($request['full_name']); ?></h6>
                                <p>
                                    Requested <strong><?php echo htmlspecialchars($request['leave_type']); ?></strong> leave 
                                    from <?php echo date('M j', strtotime($request['date_from'])); ?> 
                                    to <?php echo date('M j, Y', strtotime($request['date_to'])); ?>
                                    (<?php echo $request['days_requested']; ?> days)
                                </p>
                            </div>
                            <div class="activity-meta">
                                <div class="activity-time">
                                    <?php echo date('M j, g:i A', strtotime($request['filed_date'])); ?>
                                </div>
                                <span class="badge 
                                    <?php 
                                        echo $request['status'] === 'approved' ? 'bg-success' : 
                                             ($request['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger');
                                    ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="activity-item text-center py-4">
                        <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                        <h6 class="text-muted">No recent requests</h6>
                        <p class="text-muted mb-0">Recent leave requests will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Right Column - Charts & Department Overview -->
    <div class="col-lg-4">
        <!-- Leave Trends Chart -->
        <div class="dashboard-chart-container mb-4">
            <div class="chart-header-enhanced">
                <h5><i class="fas fa-chart-line me-2"></i>Leave Trends</h5>
            </div>
            <div class="chart-body-enhanced">
                <canvas id="leaveTrendsChart" style="width: 100%; height: 250px;"></canvas>
            </div>
        </div>
        <!-- Department Overview -->
        <?php if (mysqli_num_rows($dept_result) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>Department Overview
                </h6>
            </div>
            <div class="card-body">
                <?php while ($dept = mysqli_fetch_assoc($dept_result)): ?>
                    <div class="department-card mb-3">
                        <div class="department-header">
                            <div class="department-name"><?php echo htmlspecialchars($dept['department']); ?></div>
                            <div class="department-count"><?php echo $dept['employee_count']; ?> emp</div>
                        </div>
                        <div class="department-stats">
                            <div class="dept-stat">
                                <div class="dept-stat-number"><?php echo $dept['total_requests']; ?></div>
                                <div class="dept-stat-label">Total</div>
                            </div>
                            <div class="dept-stat">
                                <div class="dept-stat-number"><?php echo $dept['pending_requests']; ?></div>
                                <div class="dept-stat-label">Pending</div>
                            </div>
                            <div class="dept-stat">
                                <div class="dept-stat-number"><?php echo $dept['approved_requests']; ?></div>
                                <div class="dept-stat-label">Approved</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('leaveTrendsChart').getContext('2d');
    fetch('get_leave_trends.php')
        .then(response => response.json())
        .then(data => {
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Leave Requests',
                        data: data.values,
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#64748b'
                            },
                            grid: {
                                color: '#e2e8f0'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#64748b'
                            },
                            grid: {
                                color: '#e2e8f0'
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading leave trends data:', error);
            document.getElementById('leaveTrendsChart').parentElement.innerHTML = 
                '<div class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle mb-2"></i><br>Unable to load chart data</div>';
        });
});
</script>
<?php include '../includes/footer.php'; ?>
