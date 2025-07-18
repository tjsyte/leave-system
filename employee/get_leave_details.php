<?php
session_start();
require_once '../config/db_connect.php';
check_login();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<p style="color: var(--danger-color);">Invalid request ID</p>';
    exit;
}
$request_id = (int)$_GET['id'];
$query = "SELECT lr.*, u.full_name as approved_by_name 
          FROM leave_requests lr 
          LEFT JOIN users u ON lr.approved_by = u.id 
          WHERE lr.id = ? AND lr.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $request_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($request = mysqli_fetch_assoc($result)) {
    ?>
    <div style="display: grid; gap: 1rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Leave Type</label>
                <div style="padding: 0.5rem 0; font-size: 1rem;">
                    <?php echo htmlspecialchars($request['leave_type']); ?>
                </div>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Status</label>
                <div style="padding: 0.5rem 0;">
                    <span class="badge badge-<?php echo $request['status']; ?>">
                        <i class="fas fa-<?php 
                            echo $request['status'] === 'approved' ? 'check' : 
                                ($request['status'] === 'rejected' ? 'times' : 'clock'); 
                        ?>"></i>
                        <?php echo ucfirst($request['status']); ?>
                    </span>
                </div>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">From Date</label>
                <div style="padding: 0.5rem 0; font-size: 1rem;">
                    <?php echo format_date($request['date_from']); ?>
                </div>
            </div>
            <div>
                <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">To Date</label>
                <div style="padding: 0.5rem 0; font-size: 1rem;">
                    <?php echo format_date($request['date_to']); ?>
                </div>
            </div>
        </div>
        <div>
            <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Days Requested</label>
            <div style="padding: 0.5rem 0; font-size: 1rem;">
                <?php echo $request['days_requested']; ?> day<?php echo $request['days_requested'] > 1 ? 's' : ''; ?>
            </div>
        </div>
        <div>
            <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Reason</label>
            <div style="padding: 0.5rem 0; font-size: 1rem; line-height: 1.5;">
                <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
            </div>
        </div>
        <div>
            <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Filed Date</label>
            <div style="padding: 0.5rem 0; font-size: 1rem;">
                <?php echo format_date($request['filed_date']); ?>
            </div>
        </div>
        <?php if ($request['approved_by_name']): ?>
            <div>
                <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">
                    <?php echo $request['status'] === 'approved' ? 'Approved By' : 'Processed By'; ?>
                </label>
                <div style="padding: 0.5rem 0; font-size: 1rem;">
                    <?php echo htmlspecialchars($request['approved_by_name']); ?>
                    <?php if ($request['approved_date']): ?>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">
                            on <?php echo format_date($request['approved_date']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($request['manager_remarks']): ?>
            <div>
                <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Manager Remarks</label>
                <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--<?php echo $request['status'] === 'approved' ? 'success' : 'danger'; ?>-color); margin-top: 0.5rem;">
                    <?php echo nl2br(htmlspecialchars($request['manager_remarks'])); ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($request['status'] === 'pending'): ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="edit_leave.php?id=<?php echo $request['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i>
                        Edit Request
                    </a>
                    <a href="my_leaves.php?delete=<?php echo $request['id']; ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirmDelete('Are you sure you want to delete this leave request?')">
                        <i class="fas fa-trash"></i>
                        Delete Request
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
} else {
    echo '<p style="color: var(--danger-color);">Leave request not found or access denied</p>';
}
?>
