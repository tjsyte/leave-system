<?php
$page_title = "Leave Balance";
require_once '../includes/table_component.php';
include '../includes/header.php';
$balance_query = "SELECT * FROM leave_balances WHERE user_id = ? ORDER BY leave_type";
$stmt = mysqli_prepare($conn, $balance_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$balance_result = mysqli_stmt_get_result($stmt);
$usage_query = "SELECT 
    leave_type,
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as days_used,
    SUM(CASE WHEN status = 'pending' THEN days_requested ELSE 0 END) as days_pending
FROM leave_requests 
WHERE user_id = ? 
GROUP BY leave_type
ORDER BY leave_type";
$stmt = mysqli_prepare($conn, $usage_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$usage_result = mysqli_stmt_get_result($stmt);
$usage_data = [];
while ($usage = mysqli_fetch_assoc($usage_result)) {
    $usage_data[$usage['leave_type']] = $usage;
}
$recent_query = "SELECT * FROM leave_requests 
                WHERE user_id = ? 
                ORDER BY filed_date DESC 
                LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$recent_result = mysqli_stmt_get_result($stmt);
$total_earned = 0;
$total_used = 0;
$total_balance = 0;
mysqli_data_seek($balance_result, 0);
while ($balance = mysqli_fetch_assoc($balance_result)) {
    $total_earned += $balance['total_earned'];
    $total_used += $balance['used'];
    $total_balance += $balance['balance'];
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Leave Balance</h1>
        <p class="text-muted">Track your leave credits and usage</p>
    </div>
    <div class="d-flex gap-2">
        <a href="file_leave.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>File Leave Request
        </a>
        <a href="my_leaves.php" class="btn btn-outline-primary">
            <i class="fas fa-list me-2"></i>My Requests
        </a>
    </div>
</div>
<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $total_earned; ?></h3>
                        <p class="mb-0">Total Earned</p>
                    </div>
                    <i class="fas fa-calendar-plus fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $total_used; ?></h3>
                        <p class="mb-0">Days Used</p>
                    </div>
                    <i class="fas fa-calendar-minus fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $total_balance; ?></h3>
                        <p class="mb-0">Available Balance</p>
                    </div>
                    <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo date('Y'); ?></h3>
                        <p class="mb-0">Current Year</p>
                    </div>
                    <i class="fas fa-calendar fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <!-- Leave Balance Details -->
        <?php if (mysqli_num_rows($balance_result) > 0): ?>
            <div class="card shadow-sm leave-balance-card">
                <div class="card-header bg-gradient-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-chart-bar me-2"></i>Leave Balance Details
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-light" onclick="exportLeaveBalance()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <button class="btn btn-sm btn-outline-light" onclick="printLeaveBalance()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Search Bar -->
                <div class="card-body border-bottom bg-light">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="leaveBalanceSearch" class="form-control border-start-0" 
                                       placeholder="Search leave types...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-end">
                                <small class="text-muted" id="balanceInfo">
                                    Showing <?php echo mysqli_num_rows($balance_result); ?> leave types
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Clean Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 leave-balance-enhanced" id="leaveBalanceTable">
                        <thead class="table-dark">
                            <tr>
                                <th class="border-0 sortable" data-sort="leave_type">
                                    <div class="d-flex align-items-center justify-content-between">
                                        Leave Type
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    </div>
                                </th>
                                <th class="border-0 text-center sortable" data-sort="earned">
                                    <div class="d-flex align-items-center justify-content-center">
                                        Earned
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    </div>
                                </th>
                                <th class="border-0 text-center sortable" data-sort="used">
                                    <div class="d-flex align-items-center justify-content-center">
                                        Used
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    </div>
                                </th>
                                <th class="border-0 text-center sortable" data-sort="available">
                                    <div class="d-flex align-items-center justify-content-center">
                                        Available
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    </div>
                                </th>
                                <th class="border-0 text-center">Usage Progress</th>
                                <th class="border-0 text-center d-none d-lg-table-cell">Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            mysqli_data_seek($balance_result, 0);
                            while ($balance = mysqli_fetch_assoc($balance_result)):
                                $usage = $usage_data[$balance['leave_type']] ?? null;
                                $usage_percent = $balance['total_earned'] > 0 ? 
                                               round(($balance['used'] / $balance['total_earned']) * 100, 1) : 0;
                                $progress_color = $usage_percent > 80 ? 'danger' : ($usage_percent > 60 ? 'warning' : 'success');
                                $status_class = $balance['balance'] == 0 ? 'exhausted' : ($balance['balance'] <= 2 ? 'low' : 'available');
                            ?>
                            <tr class="leave-row" data-status="<?php echo $status_class; ?>">
                                <!-- Leave Type -->
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <div class="leave-type-icon me-3">
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($balance['leave_type']); ?></div>
                                            <small class="text-muted d-block d-md-none">
                                                <?php echo $balance['used']; ?>/<?php echo $balance['total_earned']; ?> used
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <!-- Earned -->
                                <td class="text-center">
                                    <div class="stat-number text-primary fw-bold fs-5">
                                        <?php echo $balance['total_earned']; ?>
                                    </div>
                                    <small class="text-muted d-none d-md-block">days</small>
                                </td>
                                <!-- Used -->
                                <td class="text-center">
                                    <div class="stat-number text-warning fw-bold fs-5">
                                        <?php echo $balance['used']; ?>
                                    </div>
                                    <?php if ($usage && $usage['days_pending'] > 0): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-secondary badge-sm">
                                                <?php echo $usage['days_pending']; ?> pending
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted d-none d-md-block">used</small>
                                    <?php endif; ?>
                                </td>
                                <!-- Available -->
                                <td class="text-center">
                                    <div class="stat-number text-success fw-bold fs-4">
                                        <?php echo $balance['balance']; ?>
                                    </div>
                                    <small class="text-muted d-none d-md-block">available</small>
                                    <?php if ($balance['balance'] <= 2 && $balance['balance'] > 0): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-warning badge-sm">Low</span>
                                        </div>
                                    <?php elseif ($balance['balance'] == 0): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-danger badge-sm">Exhausted</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <!-- Usage Progress -->
                                <td class="text-center">
                                    <div class="progress-container">
                                        <div class="progress mb-2" style="height: 12px;">
                                            <div class="progress-bar bg-<?php echo $progress_color; ?> progress-animated" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $usage_percent; ?>%"
                                                 data-bs-toggle="tooltip" 
                                                 title="<?php echo $usage_percent; ?>% used">
                                            </div>
                                        </div>
                                        <small class="fw-bold text-<?php echo $progress_color; ?>">
                                            <?php echo $usage_percent; ?>%
                                        </small>
                                    </div>
                                </td>
                                <!-- Requests (Hidden on mobile) -->
                                <td class="text-center d-none d-lg-table-cell">
                                    <?php if ($usage): ?>
                                        <div class="requests-info">
                                            <div class="fw-bold text-dark mb-1 fs-6">
                                                <?php echo $usage['total_requests']; ?>
                                            </div>
                                            <div class="d-flex justify-content-center gap-1">
                                                <span class="badge bg-success badge-xs">
                                                    <?php echo $usage['approved_requests']; ?> approved
                                                </span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">
                                            <i class="fas fa-minus-circle opacity-50"></i>
                                            <div><small>No requests</small></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Table Footer with Summary -->
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-6 col-md-3">
                            <div class="border-end">
                                <div class="fw-bold text-primary fs-5"><?php echo $total_earned; ?></div>
                                <small class="text-muted">Total Earned</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border-end">
                                <div class="fw-bold text-warning fs-5"><?php echo $total_used; ?></div>
                                <small class="text-muted">Total Used</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border-end">
                                <div class="fw-bold text-success fs-5"><?php echo $total_balance; ?></div>
                                <small class="text-muted">Total Available</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold text-info fs-5">
                                <?php echo $total_earned > 0 ? round(($total_used / $total_earned) * 100, 1) : 0; ?>%
                            </div>
                            <small class="text-muted">Overall Usage</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Clean JavaScript for Table Functionality -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
                const searchInput = document.getElementById('leaveBalanceSearch');
                const tableRows = document.querySelectorAll('#leaveBalanceTable tbody tr');
                function filterTable() {
                    const searchTerm = searchInput.value.toLowerCase();
                    let visibleCount = 0;
                    tableRows.forEach(row => {
                        const leaveType = row.querySelector('td:first-child').textContent.toLowerCase();
                        const matchesSearch = leaveType.includes(searchTerm);
                        if (matchesSearch) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    document.getElementById('balanceInfo').textContent = 
                        `Showing ${visibleCount} of <?php echo mysqli_num_rows($balance_result); ?> leave types`;
                }
                searchInput.addEventListener('input', filterTable);
                document.querySelectorAll('.sortable').forEach(header => {
                    header.addEventListener('click', function() {
                        const sortType = this.dataset.sort;
                        sortTable(sortType, this);
                    });
                });
                function sortTable(sortType, headerElement) {
                    const tbody = document.querySelector('#leaveBalanceTable tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const isAscending = !headerElement.classList.contains('sort-desc');
                    document.querySelectorAll('.sortable i').forEach(icon => {
                        icon.className = 'fas fa-sort text-muted ms-1';
                    });
                    const icon = headerElement.querySelector('i');
                    icon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} text-white ms-1`;
                    document.querySelectorAll('.sortable').forEach(h => h.classList.remove('sort-desc', 'sort-asc'));
                    headerElement.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
                    rows.sort((a, b) => {
                        let aVal, bVal;
                        switch(sortType) {
                            case 'leave_type':
                                aVal = a.querySelector('td:nth-child(1)').textContent.trim();
                                bVal = b.querySelector('td:nth-child(1)').textContent.trim();
                                break;
                            case 'earned':
                                aVal = parseInt(a.querySelector('td:nth-child(2) .stat-number').textContent);
                                bVal = parseInt(b.querySelector('td:nth-child(2) .stat-number').textContent);
                                break;
                            case 'used':
                                aVal = parseInt(a.querySelector('td:nth-child(3) .stat-number').textContent);
                                bVal = parseInt(b.querySelector('td:nth-child(3) .stat-number').textContent);
                                break;
                            case 'available':
                                aVal = parseInt(a.querySelector('td:nth-child(4) .stat-number').textContent);
                                bVal = parseInt(b.querySelector('td:nth-child(4) .stat-number').textContent);
                                break;
                        }
                        if (typeof aVal === 'string') {
                            return isAscending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                        } else {
                            return isAscending ? aVal - bVal : bVal - aVal;
                        }
                    });
                    rows.forEach(row => tbody.appendChild(row));
                }
            });
            function exportLeaveBalance() {
                const table = document.getElementById('leaveBalanceTable');
                const rows = Array.from(table.querySelectorAll('tr')).filter(row => 
                    row.style.display !== 'none'
                );
                const csv = rows.map(row => 
                    Array.from(row.cells).slice(0, 6).map(cell => 
                        '"' + cell.textContent.replace(/"/g, '""').trim() + '"'
                    ).join(',')
                ).join('\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'leave_balance_<?php echo date('Y-m-d'); ?>.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
            function printLeaveBalance() {
                const printWindow = window.open('', '_blank');
                const tableHTML = document.querySelector('.leave-balance-card').outerHTML;
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Leave Balance Report</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                @media print {
                                    .btn, .d-none { display: none !important; }
                                    .table { font-size: 12px; }
                                    .card { box-shadow: none; border: 1px solid #000; }
                                }
                                body { font-family: Arial, sans-serif; }
                            </style>
                        </head>
                        <body class="p-3">
                            <h2 class="mb-3">Leave Balance Report - <?php echo date('F d, Y'); ?></h2>
                            ${tableHTML}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
            </script>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times text-primary mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                        <h4 class="text-muted mb-3">No Leave Balance Found</h4>
                        <p class="text-muted mb-4">Your leave balances haven't been set up yet. Please contact HR to initialize your leave credits.</p>
                        <a href="mailto:hr@company.com" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Contact HR
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-4">
        <!-- Recent Leave Requests -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Recent Requests
                </h6>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($recent_result) > 0): ?>
                    <?php while ($recent = mysqli_fetch_assoc($recent_result)): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($recent['leave_type']); ?></div>
                                <div class="small text-muted">
                                    <?php echo format_date($recent['date_from']); ?> - <?php echo format_date($recent['date_to']); ?>
                                </div>
                                <div class="small text-muted">
                                    Filed: <?php echo format_date($recent['filed_date']); ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $recent['status'] === 'approved' ? 'success' : ($recent['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($recent['status']); ?>
                                </span>
                                <div class="small text-muted mt-1">
                                    <?php echo $recent['days_requested']; ?> day<?php echo $recent['days_requested'] > 1 ? 's' : ''; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <div class="text-center">
                        <a href="my_leaves.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-2"></i>View All Requests
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-inbox mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mb-0">No recent requests</p>
                        <a href="file_leave.php" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-plus me-2"></i>File Request
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Leave Policy Info -->
        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Leave Policy
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-2">
                        <strong>Annual Leave:</strong> 15 days per year
                    </div>
                    <div class="mb-2">
                        <strong>Sick Leave:</strong> 10 days per year
                    </div>
                    <div class="mb-2">
                        <strong>Personal Leave:</strong> 5 days per year
                    </div>
                    <div class="mb-2">
                        <strong>Carry Forward:</strong> Max 5 days to next year
                    </div>
                    <div class="text-muted">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Unused leave expires at year end
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php 
if (function_exists('render_table_scripts')) {
    render_table_scripts();
}
?>
<?php include '../includes/footer.php'; ?>
