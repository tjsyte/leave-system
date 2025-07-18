<?php
session_start();
require_once '../config/db_connect.php';
check_login();
check_manager();
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
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leave_requests_export_' . date('Y-m-d') . '.csv"');
$output = fopen('php:
fputcsv($output, ['Leave Requests Export - ' . date('Y-m-d H:i:s')]);
fputcsv($output, []);
fputcsv($output, ['Employee Name', 'Department', 'Email', 'Leave Type', 'From Date', 'To Date', 'Days Requested', 'Status', 'Filed Date', 'Approved By', 'Approved Date', 'Manager Remarks', 'Reason']);
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
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['full_name'],
        $row['department'],
        $row['email'],
        $row['leave_type'],
        $row['date_from'],
        $row['date_to'],
        $row['days_requested'],
        ucfirst($row['status']),
        $row['filed_date'],
        $row['approved_by_name'] ?? 'N/A',
        $row['approved_date'] ?? 'N/A',
        $row['manager_remarks'] ?? 'N/A',
        $row['reason']
    ]);
}
fputcsv($output, []);
fputcsv($output, ['Export Summary:']);
fputcsv($output, ['Total Records: ' . mysqli_num_rows($result)]);
fputcsv($output, ['Export Date: ' . date('Y-m-d H:i:s')]);
fputcsv($output, ['Exported By: ' . $_SESSION['full_name']]);
if (!empty($status_filter) || !empty($department_filter) || !empty($type_filter) || !empty($employee_filter) || !empty($date_from) || !empty($date_to) || !empty($search)) {
    fputcsv($output, []);
    fputcsv($output, ['Applied Filters:']);
    if (!empty($status_filter)) fputcsv($output, ['Status: ' . $status_filter]);
    if (!empty($department_filter)) fputcsv($output, ['Department: ' . $department_filter]);
    if (!empty($type_filter)) fputcsv($output, ['Leave Type: ' . $type_filter]);
    if (!empty($date_from)) fputcsv($output, ['From Date: ' . $date_from]);
    if (!empty($date_to)) fputcsv($output, ['To Date: ' . $date_to]);
    if (!empty($search)) fputcsv($output, ['Search: ' . $search]);
}
fclose($output);
exit();
?>
