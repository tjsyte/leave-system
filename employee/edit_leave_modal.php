<?php
session_start();
require_once '../config/db_connect.php';
check_login();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid request ID</div>';
    exit;
}
$request_id = (int)$_GET['id'];
$query = "SELECT * FROM leave_requests WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $request_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$request = mysqli_fetch_assoc($result)) {
    echo '<div class="alert alert-danger">Leave request not found or cannot be edited</div>';
    exit;
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
?>
<form id="editLeaveModalForm" data-request-id="<?php echo $request_id; ?>">
    <div class="mb-3">
        <label for="modal_leave_type" class="form-label">
            <i class="fas fa-list me-2"></i>Leave Type *
        </label>
        <select id="modal_leave_type" name="leave_type" class="form-select" required>
            <option value="">Select Leave Type</option>
            <?php foreach ($leave_types as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $request['leave_type'] === $type ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type); ?>
                </option>
            <?php endforeach; ?>
            <?php if (!in_array($request['leave_type'], $leave_types)): ?>
                <option value="Others" selected>Others</option>
            <?php endif; ?>
        </select>
    </div>
    <div class="mb-3" id="modal_otherLeaveGroup" style="display: <?php echo (!in_array($request['leave_type'], $leave_types)) ? 'block' : 'none'; ?>;">
        <label for="modal_other_leave_type" class="form-label">
            <i class="fas fa-edit me-2"></i>Specify Other Leave Type *
        </label>
        <input type="text" id="modal_other_leave_type" name="other_leave_type" class="form-control" 
               placeholder="Please specify the leave type" 
               value="<?php echo !in_array($request['leave_type'], $leave_types) ? htmlspecialchars($request['leave_type']) : ''; ?>">
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="modal_date_from" class="form-label">
                <i class="fas fa-calendar-alt me-2"></i>From Date *
            </label>
            <input type="date" id="modal_date_from" name="date_from" class="form-control" 
                   min="<?php echo date('Y-m-d'); ?>" required
                   value="<?php echo $request['date_from']; ?>">
        </div>
        <div class="col-md-6">
            <label for="modal_date_to" class="form-label">
                <i class="fas fa-calendar-alt me-2"></i>To Date *
            </label>
            <input type="date" id="modal_date_to" name="date_to" class="form-control" 
                   min="<?php echo date('Y-m-d'); ?>" required
                   value="<?php echo $request['date_to']; ?>">
        </div>
    </div>
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="form-label">Days Requested:</span>
            <span id="modal_daysCount" class="badge bg-primary fs-6">
                <?php echo $request['days_requested']; ?> day<?php echo $request['days_requested'] > 1 ? 's' : ''; ?>
            </span>
        </div>
    </div>
    <div class="mb-3">
        <label for="modal_reason" class="form-label">
            <i class="fas fa-comment me-2"></i>Reason for Leave *
        </label>
        <textarea id="modal_reason" name="reason" class="form-control" rows="4" 
                  placeholder="Please provide a detailed reason for your leave request" required><?php echo htmlspecialchars($request['reason']); ?></textarea>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const leaveTypeSelect = document.getElementById('modal_leave_type');
    const otherLeaveGroup = document.getElementById('modal_otherLeaveGroup');
    const otherLeaveInput = document.getElementById('modal_other_leave_type');
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
    const fromDate = document.getElementById('modal_date_from');
    const toDate = document.getElementById('modal_date_to');
    const result = document.getElementById('modal_daysCount');
    function updateDays() {
        if (fromDate.value && toDate.value) {
            const from = new Date(fromDate.value);
            const to = new Date(toDate.value);
            const timeDiff = to.getTime() - from.getTime();
            const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            if (dayDiff > 0) {
                result.textContent = dayDiff + ' day' + (dayDiff > 1 ? 's' : '');
                result.className = 'badge bg-primary fs-6';
            } else {
                result.textContent = 'Invalid date range';
                result.className = 'badge bg-danger fs-6';
            }
        }
    }
    fromDate.addEventListener('change', updateDays);
    toDate.addEventListener('change', updateDays);
    fromDate.addEventListener('change', function() {
        toDate.min = this.value;
        updateDays();
    });
    updateDays();
});
</script>
