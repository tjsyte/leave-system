<?php
$page_title = "All Leave Requests";
require_once '../includes/table_component.php';
include '../includes/header.php';
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
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$department_filter = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$employee_filter = isset($_GET['employee']) ? sanitize_input($_GET['employee']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where_conditions = ["1=1"];
$params = [];
$param_types = "";
if (!empty($status_filter)) {
    $where_conditions[] = "lr.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}
if (!empty($department_filter)) {
    $where_conditions[] = "u.department = ?";
    $params[] = $department_filter;
    $param_types .= "s";
}
if (!empty($type_filter)) {
    $where_conditions[] = "lr.leave_type = ?";
    $params[] = $type_filter;
    $param_types .= "s";
}
if (!empty($employee_filter)) {
    $where_conditions[] = "lr.user_id = ?";
    $params[] = $employee_filter;
    $param_types .= "i";
}
if (!empty($date_from)) {
    $where_conditions[] = "lr.date_from >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}
if (!empty($date_to)) {
    $where_conditions[] = "lr.date_to <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}
if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR lr.leave_type LIKE ? OR lr.reason LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}
$where_clause = implode(" AND ", $where_conditions);
$query = "SELECT lr.*, u.full_name, u.department, u.email, 
                 approver.full_name as approved_by_name
          FROM leave_requests lr 
          JOIN users u ON lr.user_id = u.id 
          LEFT JOIN users approver ON lr.approved_by = approver.id
          WHERE {$where_clause} 
          ORDER BY lr.filed_date DESC";
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}
$departments_query = "SELECT DISTINCT department FROM users WHERE role = 'employee' ORDER BY department";
$departments_result = mysqli_query($conn, $departments_query);
$employees_query = "SELECT id, full_name, department FROM users WHERE role = 'employee' ORDER BY full_name";
$employees_result = mysqli_query($conn, $employees_query);
$types_query = "SELECT DISTINCT leave_type FROM leave_requests ORDER BY leave_type";
$types_result = mysqli_query($conn, $types_query);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">All Leave Requests</h1>
        <p class="text-muted">Manage all employee leave requests</p>
    </div>
    <div class="d-flex gap-2">
        <a href="pending_approvals.php" class="btn btn-warning">
            <i class="fas fa-clock me-2"></i>Pending Approvals
        </a>
        <a href="export_leaves.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
            <i class="fas fa-download me-2"></i>Export CSV
        </a>
    </div>
</div>
<!-- Advanced Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-filter me-2"></i>Advanced Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Employee, type, reason..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php while ($dept = mysqli_fetch_assoc($departments_result)): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                    <?php echo $department_filter === $dept['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Leave Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <?php while ($type = mysqli_fetch_assoc($types_result)): ?>
                            <option value="<?php echo htmlspecialchars($type['leave_type']); ?>" 
                                    <?php echo $type_filter === $type['leave_type'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['leave_type']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employee</label>
                    <select name="employee" class="form-select">
                        <option value="">All Employees</option>
                        <?php while ($emp = mysqli_fetch_assoc($employees_result)): ?>
                            <option value="<?php echo $emp['id']; ?>" 
                                    <?php echo $employee_filter == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['full_name']) . ' (' . htmlspecialchars($emp['department']) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                        <a href="all_leaves.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear All
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php if (mysqli_num_rows($result) > 0): ?>
    <?php
    $table_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $status_badge = '<span class="badge bg-' . 
                       ($row['status'] === 'approved' ? 'success' : 
                        ($row['status'] === 'rejected' ? 'danger' : 'warning')) . '">' .
                       '<i class="fas fa-' . 
                       ($row['status'] === 'approved' ? 'check' : 
                        ($row['status'] === 'rejected' ? 'times' : 'clock')) . ' me-1"></i>' .
                       ucfirst($row['status']) . '</span>';
        $employee_info = '<div>' .
                        '<strong>' . htmlspecialchars($row['full_name']) . '</strong>' .
                        '<div class="small text-muted">' . htmlspecialchars($row['department']) . '</div>' .
                        '<div class="small text-muted">' . htmlspecialchars($row['email']) . '</div>' .
                        '</div>';
        $dates_info = '<div class="small">' .
                     '<div><strong>From:</strong> ' . format_date($row['date_from']) . '</div>' .
                     '<div><strong>To:</strong> ' . format_date($row['date_to']) . '</div>' .
                     '</div>';
        $days_badge = '<span class="badge bg-light text-dark border">' .
                     $row['days_requested'] . ' day' . ($row['days_requested'] > 1 ? 's' : '') .
                     '</span>';
        $approved_by = $row['approved_by_name'] ? 
                      '<div class="small">' . htmlspecialchars($row['approved_by_name']) . 
                      ($row['approved_date'] ? '<div class="text-muted">' . format_date($row['approved_date']) . '</div>' : '') .
                      '</div>' : 
                      '<span class="text-muted">-</span>';
        $actions = '<div class="btn-group btn-group-sm" role="group">' .
                  '<button type="button" class="btn btn-outline-primary" onclick="viewDetails(' . $row['id'] . ')" title="View Details">' .
                  '<i class="fas fa-eye"></i></button>';
        if ($row['status'] === 'pending') {
            $actions .= '<button type="button" class="btn btn-outline-success" onclick="processRequest(' . $row['id'] . ', \'approved\')" title="Approve">' .
                       '<i class="fas fa-check"></i></button>' .
                       '<button type="button" class="btn btn-outline-danger" onclick="processRequest(' . $row['id'] . ', \'rejected\')" title="Reject">' .
                       '<i class="fas fa-times"></i></button>';
        }
        $actions .= '</div>';
        $table_data[] = [
            $employee_info,
            '<strong>' . htmlspecialchars($row['leave_type']) . '</strong>',
            $dates_info,
            $days_badge,
            $status_badge,
            '<div class="small">' . format_date($row['filed_date']) . '</div>',
            $approved_by,
            $actions
        ];
    }
    $columns = ['Employee', 'Leave Type', 'Dates', 'Days', 'Status', 'Filed Date', 'Approved By', 'Actions'];
    render_data_table('allLeavesTable', $columns, $table_data, [
        'search' => true,
        'pagination' => true,
        'per_page' => 15,
        'table_class' => 'table table-striped table-hover align-middle',
        'container_class' => 'card shadow-sm'
    ]);
    render_table_scripts();
    ?>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-search text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
            <h4 class="text-muted">No Leave Requests Found</h4>
            <p class="text-muted">No leave requests match your current filters.</p>
            <a href="all_leaves.php" class="btn btn-outline-primary">
                <i class="fas fa-refresh me-2"></i>Clear Filters
            </a>
        </div>
    </div>
<?php endif; ?>
<!-- Request Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Leave Request Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
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
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                                  placeholder="Add your comments or reasons..."></textarea>
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
function viewDetails(requestId) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
    fetch(`get_request_details.php?id=${requestId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('modalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('modalContent').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading details</div>';
        });
}
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
<?php include '../includes/footer.php'; ?>
