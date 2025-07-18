<?php
$page_title = "Reports & Analytics";
include '../includes/header.php';
check_manager();
$current_year = date('Y');
$summary_query = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as total_approved_days,
                    AVG(CASE WHEN status = 'approved' THEN days_requested ELSE NULL END) as avg_leave_duration
                  FROM leave_requests 
                  WHERE YEAR(filed_date) = ?";
$stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($stmt, "i", $current_year);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$monthly_query = "SELECT 
                    MONTH(filed_date) as month,
                    MONTHNAME(filed_date) as month_name,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as approved_days
                  FROM leave_requests 
                  WHERE YEAR(filed_date) = ?
                  GROUP BY MONTH(filed_date), MONTHNAME(filed_date)
                  ORDER BY MONTH(filed_date)";
$stmt = mysqli_prepare($conn, $monthly_query);
mysqli_stmt_bind_param($stmt, "i", $current_year);
mysqli_stmt_execute($stmt);
$monthly_result = mysqli_stmt_get_result($stmt);
$type_query = "SELECT 
                 leave_type,
                 COUNT(*) as total_requests,
                 SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                 SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as approved_days,
                 AVG(CASE WHEN status = 'approved' THEN days_requested ELSE NULL END) as avg_duration
               FROM leave_requests 
               WHERE YEAR(filed_date) = ?
               GROUP BY leave_type
               ORDER BY total_requests DESC";
$stmt = mysqli_prepare($conn, $type_query);
mysqli_stmt_bind_param($stmt, "i", $current_year);
mysqli_stmt_execute($stmt);
$type_result = mysqli_stmt_get_result($stmt);
$dept_query = "SELECT 
                 u.department,
                 COUNT(lr.id) as total_requests,
                 SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved,
                 SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as approved_days,
                 COUNT(DISTINCT u.id) as employee_count
               FROM users u 
               LEFT JOIN leave_requests lr ON u.id = lr.user_id AND YEAR(lr.filed_date) = ?
               WHERE u.role = 'employee'
               GROUP BY u.department
               ORDER BY total_requests DESC";
$stmt = mysqli_prepare($conn, $dept_query);
mysqli_stmt_bind_param($stmt, "i", $current_year);
mysqli_stmt_execute($stmt);
$dept_result = mysqli_stmt_get_result($stmt);
$employee_query = "SELECT 
                     u.full_name,
                     u.department,
                     COUNT(lr.id) as total_requests,
                     SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as approved_days
                   FROM users u 
                   LEFT JOIN leave_requests lr ON u.id = lr.user_id AND YEAR(lr.filed_date) = ?
                   WHERE u.role = 'employee'
                   GROUP BY u.id, u.full_name, u.department
                   HAVING total_requests > 0
                   ORDER BY approved_days DESC
                   LIMIT 10";
$stmt = mysqli_prepare($conn, $employee_query);
mysqli_stmt_bind_param($stmt, "i", $current_year);
mysqli_stmt_execute($stmt);
$employee_result = mysqli_stmt_get_result($stmt);
?>
<!-- Summary Statistics -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon primary">
                <i class="fas fa-chart-bar"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo $summary['total_requests']; ?></div>
        <div class="stat-label">Total Requests (<?php echo $current_year; ?>)</div>
    </div>
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo $summary['approved_requests']; ?></div>
        <div class="stat-label">Approved Requests</div>
    </div>
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon info">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo $summary['total_approved_days']; ?></div>
        <div class="stat-label">Total Leave Days</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($summary['avg_leave_duration'] ?? 0, 1); ?></div>
        <div class="stat-label">Avg. Leave Duration</div>
    </div>
</div>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Monthly Trends -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-line"></i> Monthly Leave Trends (<?php echo $current_year; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($monthly_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total</th>
                                <th>Approved</th>
                                <th>Rejected</th>
                                <th>Pending</th>
                                <th>Days Taken</th>
                                <th>Approval Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($month = mysqli_fetch_assoc($monthly_result)): ?>
                                <?php $approval_rate = $month['total_requests'] > 0 ? ($month['approved'] / $month['total_requests']) * 100 : 0; ?>
                                <tr>
                                    <td><strong><?php echo $month['month_name']; ?></strong></td>
                                    <td><?php echo $month['total_requests']; ?></td>
                                    <td>
                                        <span style="color: var(--success-color); font-weight: 600;">
                                            <?php echo $month['approved']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: var(--danger-color); font-weight: 600;">
                                            <?php echo $month['rejected']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: var(--warning-color); font-weight: 600;">
                                            <?php echo $month['pending']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: var(--primary-color);">
                                            <?php echo $month['approved_days']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: <?php echo $approval_rate > 80 ? 'var(--success-color)' : ($approval_rate > 60 ? 'var(--warning-color)' : 'var(--danger-color)'); ?>">
                                            <?php echo round($approval_rate); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No data available for this year</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Quick Actions -->
    <div>
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <h5><i class="fas fa-download"></i> Export Reports</h5>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <button onclick="exportReport('monthly')" class="btn btn-outline">
                        <i class="fas fa-file-csv"></i>
                        Monthly Report
                    </button>
                    <button onclick="exportReport('department')" class="btn btn-outline">
                        <i class="fas fa-building"></i>
                        Department Report
                    </button>
                    <button onclick="exportReport('employee')" class="btn btn-outline">
                        <i class="fas fa-users"></i>
                        Employee Report
                    </button>
                    <button onclick="exportReport('detailed')" class="btn btn-outline">
                        <i class="fas fa-file-alt"></i>
                        Detailed Report
                    </button>
                    <button onclick="window.print()" class="btn btn-outline">
                        <i class="fas fa-print"></i>
                        Print Report
                    </button>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Report Summary</h5>
            </div>
            <div class="card-body">
                <div style="font-size: 0.875rem; line-height: 1.6;">
                    <div style="margin-bottom: 1rem;">
                        <strong>Approval Rate:</strong>
                        <div style="margin-top: 0.25rem;">
                            <?php 
                            $approval_rate = $summary['total_requests'] > 0 ? ($summary['approved_requests'] / $summary['total_requests']) * 100 : 0;
                            ?>
                            <div style="width: 100%; height: 8px; background: var(--light-color); border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo $approval_rate; ?>%; height: 100%; background: <?php echo $approval_rate > 80 ? 'var(--success-color)' : ($approval_rate > 60 ? 'var(--warning-color)' : 'var(--danger-color)'); ?>; transition: width 0.3s ease;"></div>
                            </div>
                            <span style="font-weight: 600; color: <?php echo $approval_rate > 80 ? 'var(--success-color)' : ($approval_rate > 60 ? 'var(--warning-color)' : 'var(--danger-color)'); ?>">
                                <?php echo round($approval_rate); ?>%
                            </span>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Key Insights:</strong>
                        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                            <li>Average leave duration: <?php echo number_format($summary['avg_leave_duration'] ?? 0, 1); ?> days</li>
                            <li>Pending requests: <?php echo $summary['pending_requests']; ?></li>
                            <li>Total days approved: <?php echo $summary['total_approved_days']; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Leave Type Analysis -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h5><i class="fas fa-chart-pie"></i> Leave Type Analysis</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Total Requests</th>
                        <th>Approved</th>
                        <th>Approval Rate</th>
                        <th>Total Days</th>
                        <th>Avg. Duration</th>
                        <th>Popularity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_all_requests = $summary['total_requests'];
                    while ($type = mysqli_fetch_assoc($type_result)): 
                        $type_approval_rate = $type['total_requests'] > 0 ? ($type['approved'] / $type['total_requests']) * 100 : 0;
                        $popularity = $total_all_requests > 0 ? ($type['total_requests'] / $total_all_requests) * 100 : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($type['leave_type']); ?></strong></td>
                            <td><?php echo $type['total_requests']; ?></td>
                            <td>
                                <span style="color: var(--success-color); font-weight: 600;">
                                    <?php echo $type['approved']; ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: <?php echo $type_approval_rate > 80 ? 'var(--success-color)' : ($type_approval_rate > 60 ? 'var(--warning-color)' : 'var(--danger-color)'); ?>">
                                    <?php echo round($type_approval_rate); ?>%
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--primary-color);">
                                    <?php echo $type['approved_days']; ?>
                                </span>
                            </td>
                            <td><?php echo number_format($type['avg_duration'] ?? 0, 1); ?> days</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 60px; height: 6px; background: var(--light-color); border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?php echo $popularity; ?>%; height: 100%; background: var(--primary-color); transition: width 0.3s ease;"></div>
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);">
                                        <?php echo round($popularity); ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Department Analysis -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-building"></i> Department Analysis</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Employees</th>
                            <th>Requests</th>
                            <th>Days Taken</th>
                            <th>Avg/Employee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($dept = mysqli_fetch_assoc($dept_result)): ?>
                            <?php $avg_per_employee = $dept['employee_count'] > 0 ? ($dept['approved_days'] / $dept['employee_count']) : 0; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dept['department']); ?></strong></td>
                                <td><?php echo $dept['employee_count']; ?></td>
                                <td><?php echo $dept['total_requests']; ?></td>
                                <td>
                                    <span style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo $dept['approved_days']; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($avg_per_employee, 1); ?> days</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Top Leave Users -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-trophy"></i> Top Leave Users</h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($employee_result) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php $rank = 1; while ($employee = mysqli_fetch_assoc($employee_result)): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                            <div style="width: 30px; height: 30px; background: <?php echo $rank <= 3 ? 'var(--warning-color)' : 'var(--light-color)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: <?php echo $rank <= 3 ? 'white' : 'var(--text-secondary)'; ?>; font-size: 0.875rem;">
                                <?php echo $rank; ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($employee['department']); ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 600; color: var(--primary-color);">
                                    <?php echo $employee['approved_days']; ?> days
                                </div>
                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <?php echo $employee['total_requests']; ?> requests
                                </div>
                            </div>
                        </div>
                        <?php $rank++; ?>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No leave data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function exportReport(type) {
    const params = new URLSearchParams();
    params.set('export', 'csv');
    params.set('type', type);
    params.set('year', <?php echo $current_year; ?>);
    window.location.href = 'export_report.php?' + params.toString();
}
</script>
<style>
@media print {
    .sidebar, .top-header, .btn, .card-header h5 i {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        break-inside: avoid;
    }
    body {
        font-size: 12px !important;
    }
    .stats-grid {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}
@media (max-width: 768px) {
    div[style*="grid-template-columns: 2fr 1fr"],
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    .table-responsive {
        font-size: 0.8rem;
    }
}
</style>
<?php include '../includes/footer.php'; ?>
