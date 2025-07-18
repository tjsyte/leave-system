<?php
$page_title = "File Leave Request";
include '../includes/header.php';
$success_message = '';
$error_message = '';
$leave_types = [
    'Vacation Leave',
    'Sick Leave',
    'Maternity Leave',
    'Paternity Leave',
    'Solo Parent Leave',
    'Study Leave',
    '10-day VAWC Leave',
    'Rehabilitation Privilege',
    'Special Privilege Leave',
    'Adoption Leave',
    'Others'
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = sanitize_input($_POST['leave_type']);
    $other_leave_type = sanitize_input($_POST['other_leave_type'] ?? '');
    $date_from = sanitize_input($_POST['date_from']);
    $date_to = sanitize_input($_POST['date_to']);
    $reason = sanitize_input($_POST['reason']);
    if ($leave_type === 'Others' && !empty($other_leave_type)) {
        $leave_type = $other_leave_type;
    }
    if (empty($leave_type) || empty($date_from) || empty($date_to) || empty($reason)) {
        $error_message = 'Please fill in all required fields';
    } else {
        $from_date = new DateTime($date_from);
        $to_date = new DateTime($date_to);
        $today = new DateTime();
        if ($from_date < $today) {
            $error_message = 'Leave start date cannot be in the past';
        } elseif ($to_date < $from_date) {
            $error_message = 'Leave end date cannot be earlier than start date';
        } else {
            $days_requested = $from_date->diff($to_date)->days + 1;
            $balance_query = "SELECT balance FROM leave_balances 
                            WHERE user_id = ? AND leave_type = ?";
            $stmt = mysqli_prepare($conn, $balance_query);
            mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $leave_type);
            mysqli_stmt_execute($stmt);
            $balance_result = mysqli_stmt_get_result($stmt);
            $balance_row = mysqli_fetch_assoc($balance_result);
            $available_balance = $balance_row['balance'] ?? 0;
            if ($days_requested > $available_balance && $leave_type !== 'Others') {
                $error_message = "Insufficient leave balance. You have {$available_balance} days available for {$leave_type}";
            } else {
                $insert_query = "INSERT INTO leave_requests (user_id, leave_type, date_from, date_to, days_requested, reason, status, filed_date) 
                               VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "isssis", $_SESSION['user_id'], $leave_type, $date_from, $date_to, $days_requested, $reason);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = 'Leave request submitted successfully! Your request is now pending approval.';
                    $_POST = array();
                } else {
                    $error_message = 'Error submitting leave request. Please try again.';
                }
            }
        }
    }
}
$balances_query = "SELECT * FROM leave_balances WHERE user_id = ? ORDER BY leave_type";
$stmt = mysqli_prepare($conn, $balances_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$balances_result = mysqli_stmt_get_result($stmt);
?>
<?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>
<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Leave Request Form -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-plus-circle"></i> File New Leave Request</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="leaveForm">
                <div class="form-group">
                    <label for="leave_type" class="form-label">
                        <i class="fas fa-list"></i> Leave Type *
                    </label>
                    <select id="leave_type" name="leave_type" class="form-control form-select" required>
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leave_types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo (isset($_POST['leave_type']) && $_POST['leave_type'] === $type) ? 'selected' : ''; ?>>
                                <?php echo $type; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="otherLeaveGroup" style="display: none;">
                    <label for="other_leave_type" class="form-label">
                        <i class="fas fa-edit"></i> Specify Other Leave Type *
                    </label>
                    <input type="text" id="other_leave_type" name="other_leave_type" class="form-control" 
                           placeholder="Please specify the leave type" 
                           value="<?php echo isset($_POST['other_leave_type']) ? htmlspecialchars($_POST['other_leave_type']) : ''; ?>">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="date_from" class="form-label">
                            <i class="fas fa-calendar-alt"></i> From Date *
                        </label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required
                               value="<?php echo isset($_POST['date_from']) ? $_POST['date_from'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to" class="form-label">
                            <i class="fas fa-calendar-alt"></i> To Date *
                        </label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required
                               value="<?php echo isset($_POST['date_to']) ? $_POST['date_to'] : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                        <span class="form-label">Days Requested:</span>
                        <span id="daysCount" style="font-weight: 600; color: var(--primary-color);">0 days</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reason" class="form-label">
                        <i class="fas fa-comment"></i> Reason for Leave *
                    </label>
                    <textarea id="reason" name="reason" class="form-control" rows="4" 
                              placeholder="Please provide a detailed reason for your leave request" required><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="resetForm()">
                        <i class="fas fa-undo"></i>
                        Reset Form
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Leave Balance Summary -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-balance-scale"></i> Your Leave Balance</h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($balances_result) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php while ($balance = mysqli_fetch_assoc($balances_result)): ?>
                        <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: #f8fafc;">
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($balance['leave_type']); ?>
                            </div>
                            <div style="display: flex; justify-content: between; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                <span>Total Earned:</span>
                                <span><?php echo $balance['total_earned']; ?> days</span>
                            </div>
                            <div style="display: flex; justify-content: between; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                <span>Used:</span>
                                <span><?php echo $balance['used']; ?> days</span>
                            </div>
                            <div style="display: flex; justify-content: between; font-weight: 600; color: var(--primary-color);">
                                <span>Available:</span>
                                <span><?php echo $balance['balance']; ?> days</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <a href="leave_balance.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-eye"></i>
                        View Detailed Balance
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No leave balance information available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Tips Card -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h5><i class="fas fa-lightbulb"></i> Leave Request Tips</h5>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <div style="display: flex; align-items: start; gap: 0.75rem;">
                <div style="color: var(--primary-color); font-size: 1.25rem;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <strong>Plan Ahead</strong>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">
                        Submit your leave requests at least 2 weeks in advance for better approval chances.
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: start; gap: 0.75rem;">
                <div style="color: var(--success-color); font-size: 1.25rem;">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div>
                    <strong>Check Balance</strong>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">
                        Always verify your leave balance before submitting a request to avoid rejections.
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: start; gap: 0.75rem;">
                <div style="color: var(--warning-color); font-size: 1.25rem;">
                    <i class="fas fa-comment-alt"></i>
                </div>
                <div>
                    <strong>Clear Reason</strong>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">
                        Provide a clear and detailed reason for your leave request.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const leaveTypeSelect = document.getElementById('leave_type');
    const otherLeaveGroup = document.getElementById('otherLeaveGroup');
    const otherLeaveInput = document.getElementById('other_leave_type');
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    const daysCountSpan = document.getElementById('daysCount');
    leaveTypeSelect.addEventListener('change', function() {
        if (this.value === 'Others') {
            otherLeaveGroup.style.display = 'block';
            otherLeaveInput.required = true;
        } else {
            otherLeaveGroup.style.display = 'none';
            otherLeaveInput.required = false;
            otherLeaveInput.value = '';
        }
    });
    if (leaveTypeSelect.value === 'Others') {
        otherLeaveGroup.style.display = 'block';
        otherLeaveInput.required = true;
    }
    validateDateRange('date_from', 'date_to');
    calculateDays('date_from', 'date_to', 'daysCount');
    document.getElementById('leaveForm').addEventListener('submit', function(e) {
        if (!validateForm('leaveForm')) {
            e.preventDefault();
            alert('Please fill in all required fields');
        } else {
            setLoadingState('submitBtn', true);
        }
    });
});
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('leaveForm').reset();
        document.getElementById('otherLeaveGroup').style.display = 'none';
        document.getElementById('other_leave_type').required = false;
        document.getElementById('daysCount').textContent = '0 days';
    }
}
function calculateDays(fromId, toId, resultId) {
    const fromDate = document.getElementById(fromId);
    const toDate = document.getElementById(toId);
    const result = document.getElementById(resultId);
    function updateDays() {
        if (fromDate.value && toDate.value) {
            const from = new Date(fromDate.value);
            const to = new Date(toDate.value);
            const timeDiff = to.getTime() - from.getTime();
            const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            if (dayDiff > 0) {
                result.textContent = dayDiff + ' day' + (dayDiff > 1 ? 's' : '');
                result.style.color = 'var(--primary-color)';
            } else {
                result.textContent = 'Invalid date range';
                result.style.color = 'var(--danger-color)';
            }
        } else {
            result.textContent = '0 days';
            result.style.color = 'var(--text-secondary)';
        }
    }
    fromDate.addEventListener('change', updateDays);
    toDate.addEventListener('change', updateDays);
    updateDays();
}
</script>
<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
<?php include '../includes/footer.php'; ?>
