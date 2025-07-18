<?php
$page_title = "My Leave Requests";
require_once '../includes/table_component.php';
include '../includes/header.php';
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
$query = "SELECT lr.*, u.full_name as approved_by_name 
          FROM leave_requests lr 
          LEFT JOIN users u ON lr.approved_by = u.id 
          WHERE lr.user_id = ?
          ORDER BY lr.filed_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$requests_result = mysqli_stmt_get_result($stmt);
$types_query = "SELECT DISTINCT leave_type FROM leave_requests WHERE user_id = ? ORDER BY leave_type";
$stmt = mysqli_prepare($conn, $types_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$types_result = mysqli_stmt_get_result($stmt);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">My Leave Requests</h1>
        <p class="text-muted">View and manage your leave requests</p>
    </div>
    <a href="file_leave.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>New Request
    </a>
</div>
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (mysqli_num_rows($requests_result) > 0): ?>
    <?php
    $table_data = [];
    while ($request = mysqli_fetch_assoc($requests_result)) {
        $status_badge = '<span class="badge bg-' . 
                       ($request['status'] === 'approved' ? 'success' : 
                        ($request['status'] === 'rejected' ? 'danger' : 'warning')) . '">' .
                       '<i class="fas fa-' . 
                       ($request['status'] === 'approved' ? 'check' : 
                        ($request['status'] === 'rejected' ? 'times' : 'clock')) . ' me-1"></i>' .
                       ucfirst($request['status']) . '</span>';
        $dates_info = '<div class="small">' .
                     '<div><strong>From:</strong> ' . format_date($request['date_from']) . '</div>' .
                     '<div><strong>To:</strong> ' . format_date($request['date_to']) . '</div>' .
                     '</div>';
        $days_badge = '<span class="badge bg-light text-dark border">' .
                     $request['days_requested'] . ' day' . ($request['days_requested'] > 1 ? 's' : '') .
                     '</span>';
        $approved_by = $request['approved_by_name'] ? 
                      '<div class="small">' . htmlspecialchars($request['approved_by_name']) . 
                      ($request['approved_date'] ? '<div class="text-muted">' . format_date($request['approved_date']) . '</div>' : '') .
                      '</div>' : 
                      '<span class="text-muted">-</span>';
        $actions = '<div class="btn-group btn-group-sm" role="group">' .
                  '<button type="button" class="btn btn-outline-primary" onclick="viewDetails(' . $request['id'] . ')" title="View Details">' .
                  '<i class="fas fa-eye"></i></button>';
        if ($request['status'] === 'pending') {
            $actions .= '<button type="button" class="btn btn-outline-warning" onclick="editLeave(' . $request['id'] . ')" title="Edit">' .
                       '<i class="fas fa-edit"></i></button>' .
                       '<button type="button" class="btn btn-outline-danger" onclick="deleteLeave(' . $request['id'] . ')" title="Delete">' .
                       '<i class="fas fa-trash"></i></button>';
        }
        $actions .= '</div>';
        $table_data[] = [
            '<strong>' . htmlspecialchars($request['leave_type']) . '</strong>',
            $dates_info,
            $days_badge,
            $status_badge,
            '<div class="small">' . format_date($request['filed_date']) . '</div>',
            $approved_by,
            $actions
        ];
    }
    $columns = ['Leave Type', 'Dates', 'Days', 'Status', 'Filed Date', 'Approved By', 'Actions'];
    render_data_table('leavesTable', $columns, $table_data, [
        'search' => true,
        'pagination' => true,
        'per_page' => 10,
        'table_class' => 'table table-striped table-hover align-middle',
        'container_class' => 'card shadow-sm'
    ]);
    render_table_scripts();
    ?>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
            <h4 class="text-muted">No Leave Requests Found</h4>
            <p class="text-muted">You haven't submitted any leave requests yet.</p>
            <a href="file_leave.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>File Your First Leave Request
            </a>
        </div>
    </div>
<?php endif; ?>
<!-- Leave Details Modal -->
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
<!-- Edit Leave Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editModalContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">
                    <i class="fas fa-save me-2"></i>Update Request
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-trash-alt text-danger mb-3" style="font-size: 3rem;"></i>
                    <h6>Are you sure you want to delete this leave request?</h6>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Delete Request
                </button>
            </div>
        </div>
    </div>
</div>
<script>
let currentRequestId = null;
function viewDetails(requestId) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
    fetch(`get_leave_details.php?id=${requestId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('modalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('modalContent').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading details</div>';
        });
}
function editLeave(requestId) {
    currentRequestId = requestId;
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
    document.getElementById('editModalContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    fetch(`edit_leave_modal.php?id=${requestId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('editModalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('editModalContent').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading edit form</div>';
        });
}
function deleteLeave(requestId) {
    currentRequestId = requestId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
document.getElementById('saveEditBtn').addEventListener('click', function() {
    const form = document.getElementById('editLeaveModalForm');
    if (!form) {
        showAlert('Form not loaded properly', 'danger');
        return;
    }
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
    this.disabled = true;
    const formData = new FormData(form);
    formData.append('request_id', currentRequestId);
    fetch('update_leave.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error updating leave request', 'danger');
    })
    .finally(() => {
        this.innerHTML = '<i class="fas fa-save me-2"></i>Update Request';
        this.disabled = false;
    });
});
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    this.disabled = true;
    const formData = new FormData();
    formData.append('request_id', currentRequestId);
    fetch('delete_leave.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error deleting leave request', 'danger');
    })
    .finally(() => {
        this.innerHTML = '<i class="fas fa-trash me-2"></i>Delete Request';
        this.disabled = false;
    });
});
function showAlert(message, type) {
    const existingAlerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
    existingAlerts.forEach(alert => alert.remove());
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const pageContent = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-4');
    pageContent.parentNode.insertBefore(alertDiv, pageContent.nextSibling);
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
<?php include '../includes/footer.php'; ?>
