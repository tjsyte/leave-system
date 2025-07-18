<?php
session_start();
require_once '../config/db_connect.php';
check_login();
check_manager();
$export_type = isset($_GET['export']) ? $_GET['export'] : '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
if ($export_type !== 'csv') {
    header("Location: reports.php");
    exit();
}
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leave_report_' . $report_type . '_' . $year . '.csv"');
$output = fopen('php:
switch ($report_type) {
    case 'monthly':
        fputcsv($output, ['Monthly Leave Report - ' . $year]);
        fputcsv($output, ['Month', 'Total Requests', 'Approved', 'Rejected', 'Pending', 'Approved Days', 'Approval Rate %']);
        $query = "SELECT 
                    MONTH(filed_date) as month,
                    MONTHNAME(filed_date) as month_name,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN days_requested ELSE 0 END) as approved_days
                  FROM leave_requests 
                  WHERE YEAR(filed_date) = ?
                  GROUP BY MONTH(filed_date), MONTHNAME(filed_date)
                  ORDER BY MONTH(filed_date)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $approval_rate = $row['total_requests'] > 0 ? round(($row['approved'] / $row['total_requests']) * 100, 2) : 0;
            fputcsv($output, [
                $row['month_name'],
                $row['total_requests'],
                $row['approved'],
                $row['rejected'],
                $row['pending'],
                $row['approved_days'],
                $approval_rate
            ]);
        }
        break;
    case 'department':
        fputcsv($output, ['Department Leave Report - ' . $year]);
        fputcsv($output, ['Department', 'Employees', 'Total Requests', 'Approved Requests', 'Approved Days', 'Avg Days per Employee']);
        $query = "SELECT 
                     u.department,
                     COUNT(DISTINCT u.id) as employee_count,
                     COUNT(lr.id) as total_requests,
                     SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved,
                     SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as approved_days
                   FROM users u 
                   LEFT JOIN leave_requests lr ON u.id = lr.user_id AND YEAR(lr.filed_date) = ?
                   WHERE u.role = 'employee'
                   GROUP BY u.department
                   ORDER BY total_requests DESC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $avg_per_employee = $row['employee_count'] > 0 ? round($row['approved_days'] / $row['employee_count'], 2) : 0;
            fputcsv($output, [
                $row['department'],
                $row['employee_count'],
                $row['total_requests'],
                $row['approved'],
                $row['approved_days'],
                $avg_per_employee
            ]);
        }
        break;
    case 'employee':
        fputcsv($output, ['Employee Leave Report - ' . $year]);
        fputcsv($output, ['Employee Name', 'Department', 'Total Requests', 'Approved Requests', 'Rejected Requests', 'Pending Requests', 'Total Days Approved', 'Available Balance']);
        $query = "SELECT 
                     u.full_name,
                     u.department,
                     COUNT(lr.id) as total_requests,
                     SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved,
                     SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                     SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN lr.status = 'approved' THEN lr.days_requested ELSE 0 END) as approved_days,
                     SUM(lb.balance) as total_balance
                   FROM users u 
                   LEFT JOIN leave_requests lr ON u.id = lr.user_id AND YEAR(lr.filed_date) = ?
                   LEFT JOIN leave_balances lb ON u.id = lb.user_id
                   WHERE u.role = 'employee'
                   GROUP BY u.id, u.full_name, u.department
                   ORDER BY u.full_name";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['full_name'],
                $row['department'],
                $row['total_requests'],
                $row['approved'],
                $row['rejected'],
                $row['pending'],
                $row['approved_days'],
                $row['total_balance'] ?? 0
            ]);
        }
        break;
    case 'detailed':
        fputcsv($output, ['Detailed Leave Requests Report - ' . $year]);
        fputcsv($output, ['Employee', 'Department', 'Leave Type', 'From Date', 'To Date', 'Days', 'Status', 'Filed Date', 'Approved By', 'Reason']);
        $query = "SELECT 
                     u.full_name,
                     u.department,
                     lr.leave_type,
                     lr.date_from,
                     lr.date_to,
                     lr.days_requested,
                     lr.status,
                     lr.filed_date,
                     approver.full_name as approved_by,
                     lr.reason
                   FROM leave_requests lr
                   JOIN users u ON lr.user_id = u.id
                   LEFT JOIN users approver ON lr.approved_by = approver.id
                   WHERE YEAR(lr.filed_date) = ?
                   ORDER BY lr.filed_date DESC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['full_name'],
                $row['department'],
                $row['leave_type'],
                $row['date_from'],
                $row['date_to'],
                $row['days_requested'],
                ucfirst($row['status']),
                $row['filed_date'],
                $row['approved_by'] ?? 'N/A',
                $row['reason']
            ]);
        }
        break;
    default:
        fputcsv($output, ['Error: Invalid report type']);
        break;
}
fputcsv($output, []);
fputcsv($output, ['Report generated on: ' . date('Y-m-d H:i:s')]);
fputcsv($output, ['Generated by: ' . $_SESSION['full_name']]);
fclose($output);
exit();
?>
