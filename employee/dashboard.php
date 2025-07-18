<?php
$page_title = "Employee Dashboard";
include '../includes/header.php';
$user_id = $_SESSION['user_id'];
$pending_query = "SELECT COUNT(*) as count FROM leave_requests WHERE user_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $pending_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$pending_result = mysqli_stmt_get_result($stmt);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];
$approved_query = "SELECT COUNT(*) as count FROM leave_requests WHERE user_id = ? AND status = 'approved'";
$stmt = mysqli_prepare($conn, $approved_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$approved_result = mysqli_stmt_get_result($stmt);
$approved_count = mysqli_fetch_assoc($approved_result)['count'];
$rejected_query = "SELECT COUNT(*) as count FROM leave_requests WHERE user_id = ? AND status = 'rejected'";
$stmt = mysqli_prepare($conn, $rejected_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$rejected_result = mysqli_stmt_get_result($stmt);
$rejected_count = mysqli_fetch_assoc($rejected_result)['count'];
$balance_query = "SELECT SUM(balance) as total_balance FROM leave_balances WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $balance_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$balance_result = mysqli_stmt_get_result($stmt);
$total_balance = mysqli_fetch_assoc($balance_result)['total_balance'] ?? 0;
$recent_query = "SELECT lr.*, u.full_name as approved_by_name 
                FROM leave_requests lr 
                LEFT JOIN users u ON lr.approved_by = u.id 
                WHERE lr.user_id = ? 
                ORDER BY lr.filed_date DESC 
                LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_requests = mysqli_stmt_get_result($stmt);
$upcoming_query = "SELECT * FROM leave_requests 
                  WHERE user_id = ? AND status = 'approved' AND date_from >= CURDATE() 
                  ORDER BY date_from ASC 
                  LIMIT 3";
$stmt = mysqli_prepare($conn, $upcoming_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$upcoming_leaves = mysqli_stmt_get_result($stmt);
?>
<!-- Dashboard Stats -->
<div class="stats-grid">
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo $pending_count; ?></div>
        <div class="stat-label">Pending Requests</div>
    </div>
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo $approved_count; ?></div>
        <div class="stat-label">Approved Requests</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-header">
            <div class="stat-icon danger">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo $rejected_count; ?></div>
        <div class="stat-label">Rejected Requests</div>
    </div>
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon info">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo $total_balance; ?></div>
        <div class="stat-label">Total Leave Balance</div>
    </div>
</div>
<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="file_leave.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i>
                File New Leave Request
            </a>
            <a href="my_leaves.php" class="btn btn-outline">
                <i class="fas fa-list"></i>
                View My Requests
            </a>
            <a href="leave_balance.php" class="btn btn-outline">
                <i class="fas fa-balance-scale"></i>
                Check Leave Balance
            </a>
        </div>
    </div>
</div>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
    <!-- Recent Leave Requests -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-history"></i> Recent Leave Requests</h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($recent_requests) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php while ($request = mysqli_fetch_assoc($recent_requests)): ?>
                        <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: #f8fafc;">
                            <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                                <div style="flex: 1;">
                                    <strong><?php echo htmlspecialchars($request['leave_type']); ?></strong>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                        <?php echo format_date($request['date_from']); ?> - <?php echo format_date($request['date_to']); ?>
                                    </div>
                                </div>
                                <span class="badge badge-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                Filed: <?php echo format_date($request['filed_date']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="my_leaves.php" class="btn btn-outline btn-sm">
                        View All Requests
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No leave requests yet</p>
                    <a href="file_leave.php" class="btn btn-primary btn-sm">
                        File Your First Request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Upcoming Approved Leaves -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-calendar-check"></i> Upcoming Approved Leaves</h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($upcoming_leaves) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php while ($leave = mysqli_fetch_assoc($upcoming_leaves)): ?>
                        <div style="padding: 1rem; border: 1px solid var(--success-color); border-radius: 8px; background: rgba(16, 185, 129, 0.05);">
                            <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                                <div style="flex: 1;">
                                    <strong><?php echo htmlspecialchars($leave['leave_type']); ?></strong>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                        <?php echo format_date($leave['date_from']); ?> - <?php echo format_date($leave['date_to']); ?>
                                    </div>
                                </div>
                                <span class="badge badge-approved">
                                    <?php echo $leave['days_requested']; ?> day<?php echo $leave['days_requested'] > 1 ? 's' : ''; ?>
                                </span>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--success-color);">
                                <i class="fas fa-check-circle"></i>
                                Approved
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No upcoming approved leaves</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Welcome Message -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-body">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="user-avatar" style="width: 60px; height: 60px; font-size: 1.5rem;">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
            </div>
            <div>
                <h4 style="margin-bottom: 0.5rem;">Welcome back, <?php echo $_SESSION['full_name']; ?>!</h4>
                <p style="color: var(--text-secondary); margin: 0;">
                    You're logged in as an Employee in the <?php echo $_SESSION['department']; ?> department.
                    Use the sidebar to navigate through your leave management options.
                </p>
            </div>
        </div>
    </div>
</div>
<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
<?php include '../includes/footer.php'; ?>
