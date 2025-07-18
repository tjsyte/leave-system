<?php
$page_title = "Edit Leave Request";
include '../includes/header.php';
$success_message = '';
$error_message = '';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_leaves.php");
    exit();
}
$request_id = (int)$_GET['id'];
$query = "SELECT * FROM leave_requests WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $request_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$request = mysqli_fetch_assoc($result)) {
    $_SESSION['error_message'] = 'Leave request not found or cannot be edited';
    header("Location: my_leaves.php");
    exit();
}
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
            $update_query = "UPDATE leave_requests 
                           SET leave_type = ?, date_from = ?, date_to = ?, days_requested = ?, reason = ?
                           WHERE id = ? AND user_id = ? AND status = 'pending'";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "sssissi", $leave_type, $date_from, $date_to, $days_requested, $reason, $request_id, $_SESSION['user_id']);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = 'Leave request updated successfully!';
                header("Location: my_leaves.php");
                exit();
            } else {
                $error_message = 'Error updating leave request. Please try again.';
            }
        }
    }
}
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
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-edit"></i> Edit Leave Request</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="editLeaveForm">
            <div class="form-group">
                <label for="leave_type" class="form-label">
                    <i class="fas fa-list"></i> Leave Type *
                </label>
                <select id="leave_type" name="leave_type" class="form-control form-select" required>
                    <option value="">Select Leave Type</option>
                    <?php foreach ($leave_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $request['leave_type'] === $type ? 'selected' : ''; ?>>
                            <?php echo $type; ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if (!in_array($request['leave_type'], $leave_types)): ?>
                        <option value="Others" selected>Others</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group" id="otherLeaveGroup" style="display: <?php echo (!in_array($request['leave_type'], $leave_types)) ? 'block' : 'none'; ?>;">
                <label for="other_leave_type" class="form-label">
                    <i class="fas fa-edit"></i> Specify Other Leave Type *
                </label>
                <input type="text" id="other_leave_type" name="other_leave_type" class="form-control" 
                       placeholder="Please specify the leave type" 
                       value="<?php echo !in_array($request['leave_type'], $leave_types) ? htmlspecialchars($request['leave_type']) : ''; ?>">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="date_from" class="form-label">
                        <i class="fas fa-calendar-alt"></i> From Date *
                    </label>
                    <input type="date" id="date_from" name="date_from" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" required
                           value="<?php echo $request['date_from']; ?>">
                </div>
                <div class="form-group">
                    <label for="date_to" class="form-label">
                        <i class="fas fa-calendar-alt"></i> To Date *
                    </label>
                    <input type="date" id="date_to" name="date_to" class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" required
                           value="<?php echo $request['date_to']; ?>">
                </div>
            </div>
            <div class="form-group">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                    <span class="form-label">Days Requested:</span>
                    <span id="daysCount" style="font-weight: 600; color: var(--primary-color);">
                        <?php echo $request['days_requested']; ?> day<?php echo $request['days_requested'] > 1 ? 's' : ''; ?>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label for="reason" class="form-label">
                    <i class="fas fa-comment"></i> Reason for Leave *
                </label>
                <textarea id="reason" name="reason" class="form-control" rows="4" 
                          placeholder="Please provide a detailed reason for your leave request" required><?php echo htmlspecialchars($request['reason']); ?></textarea>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="my_leaves.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to My Leaves
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i>
                    Update Request
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const leaveTypeSelect = document.getElementById('leave_type');
    const otherLeaveGroup = document.getElementById('otherLeaveGroup');
    const otherLeaveInput = document.getElementById('other_leave_type');
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
    validateDateRange('date_from', 'date_to');
    calculateDays('date_from', 'date_to', 'daysCount');
    document.getElementById('editLeaveForm').addEventListener('submit', function(e) {
        if (!validateForm('editLeaveForm')) {
            e.preventDefault();
            alert('Please fill in all required fields');
        } else {
            setLoadingState('submitBtn', true);
        }
    });
});
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
        }
    }
    fromDate.addEventListener('change', updateDays);
    toDate.addEventListener('change', updateDays);
    updateDays();
}
</script>
<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
<?php include '../includes/footer.php'; ?>
