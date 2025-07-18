<?php
session_start();
require_once '../config/db_connect.php';
check_login();
check_manager();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if (isset($_GET['for_edit'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    } else {
        echo '<p style="color: var(--danger-color);">Invalid employee ID</p>';
    }
    exit;
}
$employee_id = (int)$_GET['id'];
$for_edit = isset($_GET['for_edit']);
$query = "SELECT u.*, 
                 COUNT(lr.id) as total_requests,
                 SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as approved_days,
                 SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                 SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
                 SUM(lb.total_earned) as total_earned,
                 SUM(lb.used) as total_used,
                 SUM(lb.balance) as total_balance
          FROM users u 
          LEFT JOIN leave_requests lr ON u.id = lr.user_id 
          LEFT JOIN leave_balances lb ON u.id = lb.user_id
          WHERE u.id = ? AND u.role = 'employee'
          GROUP BY u.id";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($employee = mysqli_fetch_assoc($result)) {
    $balance_query = "SELECT * FROM leave_balances WHERE user_id = ? ORDER BY leave_type";
    $stmt = mysqli_prepare($conn, $balance_query);
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $balance_result = mysqli_stmt_get_result($stmt);
    if ($for_edit) {
        $leave_balances = [];
        while ($balance = mysqli_fetch_assoc($balance_result)) {
            $leave_balances[$balance['leave_type']] = [
                'balance' => $balance['balance'],
                'total_earned' => $balance['total_earned'],
                'used' => $balance['used']
            ];
        }
        $leave_types = [];
        $leave_types_query = "SELECT leave_type, default_balance FROM leave_types WHERE is_active = 1 ORDER BY leave_type";
        $leave_types_result = mysqli_query($conn, $leave_types_query);
        while ($row = mysqli_fetch_assoc($leave_types_result)) {
            $leave_types[] = [
                'name' => $row['leave_type'],
                'default_balance' => $row['default_balance']
            ];
        }
        echo json_encode([
            'success' => true,
            'employee' => [
                'id' => $employee['id'],
                'full_name' => $employee['full_name'],
                'email' => $employee['email'],
                'department' => $employee['department'],
                'leave_balances' => $leave_balances,
                'leave_types' => $leave_types
            ]
        ]);
        exit;
    }
    $recent_query = "SELECT * FROM leave_requests WHERE user_id = ? ORDER BY filed_date DESC LIMIT 5";
    $stmt = mysqli_prepare($conn, $recent_query);
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $recent_result = mysqli_stmt_get_result($stmt);
    ?>
    <div style="display: grid; gap: 1.5rem;">
        <!-- Employee Information -->
        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 12px; color: white;">
            <div class="user-avatar" style="width: 80px; height: 80px; font-size: 2rem; background: rgba(255,255,255,0.2);">
                <?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?>
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($employee['full_name']); ?></h4>
                <div style="opacity: 0.9; margin-bottom: 0.25rem;">
                    <i class="fas fa-user"></i> <?php echo ucfirst($employee['role']); ?> • <?php echo htmlspecialchars($employee['department']); ?>
                </div>
                <div style="opacity: 0.9;">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($employee['email']); ?>
                </div>
            </div>
        </div>
        <!-- Statistics -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                    <?php echo $employee['total_requests']; ?>
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary);">Total Requests</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--success-color);">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success-color);">
                    <?php echo $employee['approved_days']; ?>
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary);">Days Approved</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--warning-color);">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning-color);">
                    <?php echo $employee['pending_requests']; ?>
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary);">Pending</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--info-color);">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-color);">
                    <?php echo $employee['total_balance']; ?>
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary);">Available Days</div>
            </div>
        </div>
        <!-- Leave Balance Breakdown -->
        <div>
            <h6 style="margin-bottom: 1rem; color: var(--text-primary);">
                <i class="fas fa-balance-scale"></i> Leave Balance Breakdown
            </h6>
            <?php if (mysqli_num_rows($balance_result) > 0): ?>
                <div style="display: grid; gap: 0.75rem;">
                    <?php while ($balance = mysqli_fetch_assoc($balance_result)): ?>
                        <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: white;">
                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                                <strong><?php echo htmlspecialchars($balance['leave_type']); ?></strong>
                                <span style="font-weight: 600; color: var(--success-color);">
                                    <?php echo $balance['balance']; ?> available
                                </span>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; font-size: 0.875rem;">
                                <div>
                                    <span style="color: var(--text-secondary);">Earned:</span>
                                    <span style="font-weight: 600; color: var(--primary-color);"><?php echo $balance['total_earned']; ?></span>
                                </div>
                                <div>
                                    <span style="color: var(--text-secondary);">Used:</span>
                                    <span style="font-weight: 600; color: var(--danger-color);"><?php echo $balance['used']; ?></span>
                                </div>
                                <div>
                                    <span style="color: var(--text-secondary);">Balance:</span>
                                    <span style="font-weight: 600; color: var(--success-color);"><?php echo $balance['balance']; ?></span>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div style="margin-top: 0.75rem;">
                                <?php $usage_percent = $balance['total_earned'] > 0 ? ($balance['used'] / $balance['total_earned']) * 100 : 0; ?>
                                <div style="width: 100%; height: 6px; background: var(--light-color); border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $usage_percent; ?>%; height: 100%; background: <?php echo $usage_percent > 80 ? 'var(--danger-color)' : ($usage_percent > 60 ? 'var(--warning-color)' : 'var(--success-color)'); ?>; transition: width 0.3s ease;"></div>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    <?php echo round($usage_percent); ?>% used
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No leave balance data available</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- Recent Leave Requests -->
        <div>
            <h6 style="margin-bottom: 1rem; color: var(--text-primary);">
                <i class="fas fa-history"></i> Recent Leave Requests
            </h6>
            <?php if (mysqli_num_rows($recent_result) > 0): ?>
                <div style="display: grid; gap: 0.75rem;">
                    <?php while ($request = mysqli_fetch_assoc($recent_result)): ?>
                        <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: white;">
                            <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                                <div>
                                    <strong><?php echo htmlspecialchars($request['leave_type']); ?></strong>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                        <?php echo format_date($request['date_from']); ?> - <?php echo format_date($request['date_to']); ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span class="badge badge-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                        <?php echo $request['days_requested']; ?> day<?php echo $request['days_requested'] > 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                Filed: <?php echo format_date($request['filed_date']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="all_leaves.php?employee=<?php echo $employee['id']; ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-list"></i>
                        View All Requests
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No leave requests found</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- Action Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: center; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <a href="all_leaves.php?employee=<?php echo $employee['id']; ?>" class="btn btn-primary">
                <i class="fas fa-list"></i>
                View All Leave Requests
            </a>
            <a href="employees.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Back to Employees
            </a>
        </div>
    </div>
    <style>
    @media (max-width: 768px) {
        div[style*="grid-template-columns: repeat(4, 1fr)"],
        div[style*="grid-template-columns: repeat(3, 1fr)"] {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    <?php
} else {
    if ($for_edit) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    } else {
        echo '<p style="color: var(--danger-color);">Employee not found</p>';
    }
}
?>
