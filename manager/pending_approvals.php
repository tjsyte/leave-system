<?php
$page_title = "Pending Approvals";
require_once '../includes/table_component.php';
include '../includes/header.php';
require_once '../config/db_connect.php';
check_login();
check_manager();
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>' . $_SESSION['error_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['error_message']);
}
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where_conditions = ["lr.status = 'pending'"];
$params = [];
$param_types = "";
if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR lr.leave_type LIKE ? OR lr.reason LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}
$where_clause = implode(" AND ", $where_conditions);
$query = "SELECT lr.id, u.full_name, u.department, lr.leave_type, lr.date_from, lr.date_to, lr.days_requested, lr.reason, lr.filed_date
          FROM leave_requests lr
          JOIN users u ON lr.user_id = u.id
          WHERE {$where_clause}
          ORDER BY lr.filed_date ASC";
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-clock me-2"></i>Pending Leave Approvals</h1>
    <div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>
<?php if (mysqli_num_rows($result) > 0): ?>
    <?php
    $table_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $actions = '<div class="btn-group btn-group-sm" role="group">' .
                   '<button type="button" class="btn btn-outline-success" onclick="processRequest(' . $row['id'] . ', \'approved\')" title="Approve">' .
                   '<i class="fas fa-check"></i></button>' .
                   '<button type="button" class="btn btn-outline-danger" onclick="processRequest(' . $row['id'] . ', \'rejected\')" title="Reject">' .
                   '<i class="fas fa-times"></i></button>' .
                   '</div>';
        $table_data[] = [
            htmlspecialchars($row['full_name']),
            htmlspecialchars($row['department']),
            htmlspecialchars($row['leave_type']),
            htmlspecialchars($row['date_from']),
            htmlspecialchars($row['date_to']),
            htmlspecialchars($row['days_requested']),
            htmlspecialchars($row['reason']),
            htmlspecialchars($row['filed_date']),
            $actions
        ];
    }
    $columns = ['Employee', 'Department', 'Leave Type', 'Date From', 'Date To', 'Days', 'Reason', 'Filed Date', 'Actions'];
    render_data_table('pendingApprovalsTable', $columns, $table_data, [
        'search' => true,
        'pagination' => true,
        'per_page' => 15,
        'table_class' => 'table table-striped table-hover align-middle',
        'container_class' => 'card shadow-sm'
    ]);
    render_table_scripts();
    ?>
    <!-- Process Request Modal -->
    <div class="modal fade" id="processModal" tabindex="-1" aria-labelledby="processModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="processModalLabel">
                        <i class="fas fa-gavel me-2"></i>Process Leave Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="processForm" method="POST" action="process_request.php">
                    <div class="modal-body">
                        <input type="hidden" id="requestId" name="request_id">
                        <input type="hidden" id="action" name="action">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="actionText"></span>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Manager Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Add your comments or reasons..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="submitBtn">
                            <i class="fas fa-check me-2"></i>Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    function processRequest(requestId, action) {
        const modal = new bootstrap.Modal(document.getElementById('processModal'));
        const actionText = document.getElementById('actionText');
        const submitBtn = document.getElementById('submitBtn');
        document.getElementById('requestId').value = requestId;
        document.getElementById('action').value = action;
        if (action === 'approved') {
            actionText.textContent = 'You are about to APPROVE this leave request.';
            submitBtn.className = 'btn btn-success';
            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Approve Request';
        } else {
            actionText.textContent = 'You are about to REJECT this leave request.';
            submitBtn.className = 'btn btn-danger';
            submitBtn.innerHTML = '<i class="fas fa-times me-2"></i>Reject Request';
        }
        modal.show();
    }
    </script>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-clock text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
            <h4 class="text-muted">No pending leave requests found.</h4>
        </div>
    </div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
