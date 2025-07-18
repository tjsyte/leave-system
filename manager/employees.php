<?php
$page_title = "Employee Management";
require_once '../includes/table_component.php';
include '../includes/header.php';
$query = "SELECT 
    u.id,
    u.full_name,
    u.email,
    u.department,
    u.created_at,
    COUNT(lr.id) as total_requests,
    SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as total_days_taken,
    SUM(lb.balance) as total_balance
FROM users u
LEFT JOIN leave_requests lr ON u.id = lr.user_id
LEFT JOIN leave_balances lb ON u.id = lb.user_id
WHERE u.role = 'employee'
GROUP BY u.id, u.full_name, u.email, u.department, u.created_at
ORDER BY u.full_name";
$result = mysqli_query($conn, $query);
$dept_query = "SELECT 
    u.department,
    COUNT(u.id) as employee_count,
    COUNT(lr.id) as total_requests,
    SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
FROM users u
LEFT JOIN leave_requests lr ON u.id = lr.user_id
WHERE u.role = 'employee'
GROUP BY u.department
ORDER BY employee_count DESC";
$dept_result = mysqli_query($conn, $dept_query);
$stats_query = "SELECT 
    COUNT(DISTINCT u.id) as total_employees,
    COUNT(DISTINCT u.department) as total_departments,
    COUNT(lr.id) as total_requests,
    SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
FROM users u
LEFT JOIN leave_requests lr ON u.id = lr.user_id
WHERE u.role = 'employee'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Employee Management</h1>
        <p class="text-muted">Manage employees and view their leave statistics</p>
    </div>
<div class="d-flex gap-2">
        <a href="reports.php" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar me-2"></i>View Reports
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-user-plus me-2"></i>Add Employee
        </button>
    </div>
</div>
<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addEmployeeForm" action="process_request.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addEmployeeModalLabel"><i class="fas fa-user-plus me-2"></i>Add New Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add_employee">
        <div class="mb-3">
          <label for="full_name" class="form-label">Full Name</label>
          <input type="text" class="form-control" id="full_name" name="full_name" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
          <label for="department" class="form-label">Department</label>
          <input type="text" class="form-control" id="department" name="department" required>
        </div>
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Add Employee</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
function viewEmployeeDetails(employeeId) {
    const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
    modal.show();
    fetch(`get_employee_details.php?id=${employeeId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('employeeModalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('employeeModalContent').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading employee details</div>';
        });
}
</script>
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['total_employees']; ?></h3>
                        <p class="mb-0">Total Employees</p>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['total_departments']; ?></h3>
                        <p class="mb-0">Departments</p>
                    </div>
                    <i class="fas fa-building fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['total_requests']; ?></h3>
                        <p class="mb-0">Total Requests</p>
                    </div>
                    <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['pending_requests']; ?></h3>
                        <p class="mb-0">Pending Requests</p>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Department Overview -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>Department Overview
                </h6>
            </div>
            <div class="card-body">
                <?php while ($dept = mysqli_fetch_assoc($dept_result)): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?php echo htmlspecialchars($dept['department']); ?></strong>
                            <div class="small text-muted">
                                <?php echo $dept['employee_count']; ?> employees • 
                                <?php echo $dept['total_requests']; ?> requests
                            </div>
                        </div>
                        <div class="text-end">
                            <?php if ($dept['pending_requests'] > 0): ?>
                                <span class="badge bg-warning">
                                    <?php echo $dept['pending_requests']; ?> pending
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> Up to date
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="pending_approvals.php" class="btn btn-outline-warning">
                        <i class="fas fa-clock me-2"></i>
                        Review Pending Approvals (<?php echo $stats['pending_requests']; ?>)
                    </a>
                    <a href="all_leaves.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>
                        View All Leave Requests
                    </a>
                    <a href="reports.php" class="btn btn-outline-success">
                        <i class="fas fa-chart-bar me-2"></i>
                        Generate Reports
                    </a>
                    <a href="export_leaves.php" class="btn btn-outline-info">
                        <i class="fas fa-download me-2"></i>
                        Export Employee Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Employee List -->
<?php if (mysqli_num_rows($result) > 0): ?>
    <?php
    $table_data = [];
    while ($employee = mysqli_fetch_assoc($result)) {
        $employee_info = '<div>' .
                        '<strong>' . htmlspecialchars($employee['full_name']) . '</strong>' .
                        '<div class="small text-muted">' . htmlspecialchars($employee['email']) . '</div>' .
                        '</div>';
        $department_badge = '<span class="badge bg-light text-dark border">' .
                           htmlspecialchars($employee['department']) .
                           '</span>';
        $requests_info = '<div class="small">' .
                        '<div><strong>Total:</strong> ' . $employee['total_requests'] . '</div>' .
                        '<div class="text-success"><strong>Approved:</strong> ' . $employee['approved_requests'] . '</div>' .
                        '<div class="text-warning"><strong>Pending:</strong> ' . $employee['pending_requests'] . '</div>' .
                        '<div class="text-danger"><strong>Rejected:</strong> ' . $employee['rejected_requests'] . '</div>' .
                        '</div>';
        $days_info = '<div class="text-center">' .
                    '<div class="h6 mb-0">' . $employee['total_days_taken'] . '</div>' .
                    '<div class="small text-muted">days taken</div>' .
                    '</div>';
        $balance_info = '<div class="text-center">' .
                       '<div class="h6 mb-0 text-success">' . ($employee['total_balance'] ?? 0) . '</div>' .
                       '<div class="small text-muted">days left</div>' .
                       '</div>';
        $joined_date = '<div class="small">' . format_date($employee['created_at']) . '</div>';
        $actions = '<div class="btn-group btn-group-sm" role="group">' .
                  '<button type="button" class="btn btn-outline-primary" onclick="viewEmployeeDetails(' . $employee['id'] . ')" title="View Details">' .
                  '<i class="fas fa-eye"></i></button>' .
                  '<button type="button" class="btn btn-outline-secondary" onclick="openEditEmployeeModal(' . $employee['id'] . ')" title="Edit Employee">' .
                  '<i class="fas fa-edit"></i></button>' .
                  '<a href="all_leaves.php?employee=' . $employee['id'] . '" class="btn btn-outline-info" title="View Requests">' .
                  '<i class="fas fa-calendar-alt"></i></a>' .
                  '</div>';
        $table_data[] = [
            $employee_info,
            $department_badge,
            $requests_info,
            $days_info,
            $balance_info,
            $joined_date,
            $actions
        ];
    }
    $columns = ['Employee', 'Department', 'Leave Requests', 'Days Taken', 'Balance', 'Joined', 'Actions'];
    render_data_table('employeesTable', $columns, $table_data, [
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
            <i class="fas fa-users text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
            <h4 class="text-muted">No Employees Found</h4>
            <p class="text-muted">No employees are registered in the system.</p>
        </div>
    </div>
<?php endif; ?>
<!-- Employee Details Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">
                    <i class="fas fa-user me-2"></i>Employee Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="employeeModalContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function viewEmployeeDetails(employeeId) {
    const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
    modal.show();
    fetch(`get_employee_details.php?id=${employeeId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('employeeModalContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('employeeModalContent').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading employee details</div>';
        });
}
</script>
<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="editEmployeeForm" action="process_request.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editEmployeeModalLabel"><i class="fas fa-user-edit me-2"></i>Edit Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="edit_employee">
        <input type="hidden" id="edit_employee_id" name="employee_id" value="">
        <div class="mb-3">
          <label for="edit_full_name" class="form-label">Full Name</label>
          <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
        </div>
        <div class="mb-3">
          <label for="edit_email" class="form-label">Email</label>
          <input type="email" class="form-control" id="edit_email" name="email" required>
        </div>
        <div class="mb-3">
          <label for="edit_department" class="form-label">Department</label>
          <input type="text" class="form-control" id="edit_department" name="department" required>
        </div>
        <div class="mb-3">
          <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" class="form-control" id="edit_password" name="password" placeholder="Enter new password if changing">
        </div>
        <hr>
        <h6>Leave Balances</h6>
        <div>
          <select id="leaveTypeSelect" class="form-select mb-3">
            <option value="" selected disabled>Select Leave Type to Add</option>
            <!-- Leave types options will be populated here -->
          </select>
          <div id="leaveBalancesContainer">
            <!-- Leave balance inputs will be dynamically added here -->
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEditEmployeeModal(employeeId) {
    fetch(`get_employee_details.php?id=${employeeId}&for_edit=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const emp = data.employee;
                document.getElementById('edit_employee_id').value = emp.id;
                document.getElementById('edit_full_name').value = emp.full_name;
                document.getElementById('edit_email').value = emp.email;
                document.getElementById('edit_department').value = emp.department;
                document.getElementById('edit_password').value = '';
                const leaveTypeSelect = document.getElementById('leaveTypeSelect');
                leaveTypeSelect.innerHTML = '<option value="" selected disabled>Select Leave Type to Add</option>';
                emp.leave_types.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.name;
                    option.textContent = type.name;
                    option.dataset.defaultBalance = type.default_balance;
                    leaveTypeSelect.appendChild(option);
                });
                const container = document.getElementById('leaveBalancesContainer');
                container.innerHTML = '';
                function addLeaveBalanceInput(type, balance, defaultBalance = 0) {
                    const div = document.createElement('div');
                    div.className = 'mb-3 leave-balance-item border rounded p-3';
                    div.dataset.type = type;
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold">${type}</label>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-leave" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label small">Total Earned</label>
                                <input type="number" class="form-control" name="leave_balances[${type}][total_earned]" min="0" value="${defaultBalance}" placeholder="Total earned">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Used</label>
                                <input type="number" class="form-control" name="leave_balances[${type}][used]" min="0" value="0" placeholder="Days used">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Available Balance</label>
                                <input type="number" class="form-control" name="leave_balances[${type}][balance]" min="0" value="${balance}" placeholder="Available balance">
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Set the leave balance details for this employee</small>
                        </div>
                    `;
                    container.appendChild(div);
                    div.querySelector('.btn-remove-leave').addEventListener('click', () => {
                        div.remove();
                        Array.from(leaveTypeSelect.options).forEach(opt => {
                            if (opt.value === type) {
                                opt.disabled = false;
                            }
                        });
                    });
                    const totalInput = div.querySelector(`input[name="leave_balances[${type}][total_earned]"]`);
                    const usedInput = div.querySelector(`input[name="leave_balances[${type}][used]"]`);
                    const balanceInput = div.querySelector(`input[name="leave_balances[${type}][balance]"]`);
                    function updateBalance() {
                        const total = parseInt(totalInput.value) || 0;
                        const used = parseInt(usedInput.value) || 0;
                        balanceInput.value = Math.max(0, total - used);
                    }
                    totalInput.addEventListener('input', updateBalance);
                    usedInput.addEventListener('input', updateBalance);
                }
                Object.entries(emp.leave_balances).forEach(([type, balanceData]) => {
                    const balance = typeof balanceData === 'object' ? balanceData.balance : balanceData;
                    const totalEarned = typeof balanceData === 'object' ? balanceData.total_earned : balance;
                    const used = typeof balanceData === 'object' ? balanceData.used : 0;
                    addLeaveBalanceInput(type, balance, totalEarned);
                    if (typeof balanceData === 'object' && balanceData.used !== undefined) {
                        const usedInput = container.querySelector(`input[name="leave_balances[${type}][used]"]`);
                        if (usedInput) usedInput.value = balanceData.used;
                    }
                    Array.from(leaveTypeSelect.options).forEach(opt => {
                        if (opt.value === type) {
                            opt.disabled = true;
                        }
                    });
                });
                leaveTypeSelect.onchange = () => {
                    const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
                    const selectedType = selectedOption.value;
                    const defaultBalance = selectedOption.dataset.defaultBalance || 0;
                    if (selectedType) {
                        addLeaveBalanceInput(selectedType, defaultBalance, defaultBalance);
                        Array.from(leaveTypeSelect.options).forEach(opt => {
                            if (opt.value === selectedType) {
                                opt.disabled = true;
                            }
                        });
                        leaveTypeSelect.value = '';
                    }
                };
                var editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
                editModal.show();
            } else {
                alert('Failed to load employee details.');
            }
        })
        .catch(() => alert('Error loading employee details.'));
}
</script>
<?php include '../includes/footer.php'; ?>
