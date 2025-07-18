<?php
session_start();
require_once '../config/db_connect.php';
check_login();
check_manager();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<p style="color: var(--danger-color);">Invalid request ID</p>';
    exit;
}
$request_id = (int)$_GET['id'];
$query = "SELECT lr.*, u.full_name, u.email, u.department, lb.balance, lb.total_earned, lb.used,
                 approver.full_name as approved_by_name
          FROM leave_requests lr 
          JOIN users u ON lr.user_id = u.id 
          LEFT JOIN leave_balances lb ON (lr.user_id = lb.user_id AND lr.leave_type = lb.leave_type)
          LEFT JOIN users approver ON lr.approved_by = approver.id
          WHERE lr.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($request = mysqli_fetch_assoc($result)) {
    ?>
    <div style="display: grid; gap: 1.5rem;">
        <!-- Employee Information -->
        <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--primary-color);">
            <h6 style="margin-bottom: 0.75rem; color: var(--primary-color);">
                <i class="fas fa-user"></i> Employee Information
            </h6>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Full Name</label>
                    <div style="padding: 0.25rem 0; font-size: 1rem;">
                        <?php echo htmlspecialchars($request['full_name']); ?>
                    </div>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Department</label>
                    <div style="padding: 0.25rem 0; font-size: 1rem;">
                        <?php echo htmlspecialchars($request['department']); ?>
                    </div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Email</label>
                    <div style="padding: 0.25rem 0; font-size: 1rem;">
                        <?php echo htmlspecialchars($request['email']); ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Leave Request Details -->
        <div>
            <h6 style="margin-bottom: 0.75rem; color: var(--text-primary);">
                <i class="fas fa-calendar-alt"></i> Leave Request Details
            </h6>
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
                <div>
                    <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Days Requested</label>
                    <div style="padding: 0.5rem 0; font-size: 1rem;">
                        <span style="font-weight: 600; color: var(--primary-color);">
                            <?php echo $request['days_requested']; ?> day<?php echo $request['days_requested'] > 1 ? 's' : ''; ?>
                        </span>
                    </div>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Filed Date</label>
                    <div style="padding: 0.5rem 0; font-size: 1rem;">
                        <?php echo format_date($request['filed_date']); ?>
                    </div>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <label style="font-weight: 600; color: var(--text-secondary); font-size: 0.875rem;">Reason for Leave</label>
                <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px; margin-top: 0.5rem; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                </div>
            </div>
        </div>
        <!-- Leave Balance Information -->
        <?php if ($request['balance'] !== null): ?>
            <div style="padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 8px; border-left: 4px solid var(--success-color);">
                <h6 style="margin-bottom: 0.75rem; color: var(--success-color);">
                    <i class="fas fa-balance-scale"></i> Leave Balance for <?php echo htmlspecialchars($request['leave_type']); ?>
                </h6>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center;">
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                            <?php echo $request['total_earned']; ?>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">Total Earned</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger-color);">
                            <?php echo $request['used']; ?>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">Already Used</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: <?php echo $request['balance'] >= $request['days_requested'] ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                            <?php echo $request['balance']; ?>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">Available</div>
                    </div>
                </div>
                <?php if ($request['balance'] < $request['days_requested']): ?>
                    <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(239, 68, 68, 0.1); border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.2);">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--danger-color); font-weight: 600;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Insufficient Balance Warning
                        </div>
                        <div style="font-size: 0.875rem; margin-top: 0.25rem; color: var(--text-secondary);">
                            Employee is requesting <?php echo $request['days_requested']; ?> days but only has <?php echo $request['balance']; ?> days available.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- Processing Information -->
        <?php if ($request['approved_by_name'] || $request['manager_remarks']): ?>
            <div style="padding: 1rem; background: rgba(79, 70, 229, 0.05); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                <h6 style="margin-bottom: 0.75rem; color: var(--primary-color);">
                    <i class="fas fa-user-tie"></i> Processing Information
                </h6>
                <?php if ($request['approved_by_name']): ?>
                    <div style="margin-bottom: 1rem;">
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
                        <div style="padding: 0.75rem; background: white; border-radius: 8px; margin-top: 0.5rem; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($request['manager_remarks'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- Action Buttons -->
        <?php if ($request['status'] === 'pending'): ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <div style="display: flex; gap: 1rem; justify-content: flex-end; flex-wrap: wrap;">
                    <form method="POST" action="process_request.php" style="display: contents;">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success" 
                                onclick="return confirm('Are you sure you want to approve this leave request?')"
                                <?php echo ($request['balance'] !== null && $request['balance'] < $request['days_requested']) ? 'title="Warning: Insufficient balance"' : ''; ?>>
                            <i class="fas fa-check"></i>
                            Approve Request
                        </button>
                    </form>
                    <form method="POST" action="process_request.php" style="display: contents;">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to reject this leave request?')">
                            <i class="fas fa-times"></i>
                            Reject Request
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline" onclick="openRemarksModal(<?php echo $request['id']; ?>)">
                        <i class="fas fa-comment"></i>
                        Add Remarks & Process
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <!-- Remarks Modal -->
    <div id="remarksModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1001; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; max-width: 500px; width: 90%;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <h6><i class="fas fa-comment"></i> Add Remarks and Process Request</h6>
            </div>
            <form method="POST" action="process_request.php">
                <div style="padding: 1.5rem;">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    <div class="form-group">
                        <label for="remarks" class="form-label">Manager Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="4" 
                                  placeholder="Enter your remarks for this leave request..." required></textarea>
                    </div>
                </div>
                <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeRemarksModal()">
                        Cancel
                    </button>
                    <button type="submit" name="action" value="approve" class="btn btn-success">
                        <i class="fas fa-check"></i>
                        Approve with Remarks
                    </button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                        <i class="fas fa-times"></i>
                        Reject with Remarks
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openRemarksModal(requestId) {
        document.getElementById('remarksModal').style.display = 'flex';
    }
    function closeRemarksModal() {
        document.getElementById('remarksModal').style.display = 'none';
    }
    document.getElementById('remarksModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRemarksModal();
        }
    });
    </script>
    <style>
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"],
        div[style*="grid-template-columns: repeat(3, 1fr)"] {
            grid-template-columns: 1fr !important;
        }
        div[style*="display: flex; gap: 1rem; justify-content: flex-end"] {
            flex-direction: column;
        }
    }
    </style>
    <?php
} else {
    echo '<p style="color: var(--danger-color);">Leave request not found</p>';
}
?>
