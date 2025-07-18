<?php
session_start();
require_once '../config/db_connect.php';
check_login();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
if (!isset($_POST['request_id']) || !is_numeric($_POST['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}
$request_id = (int)$_POST['request_id'];
$leave_type = sanitize_input($_POST['leave_type']);
$other_leave_type = sanitize_input($_POST['other_leave_type'] ?? '');
$date_from = sanitize_input($_POST['date_from']);
$date_to = sanitize_input($_POST['date_to']);
$reason = sanitize_input($_POST['reason']);
if ($leave_type === 'Others' && !empty($other_leave_type)) {
    $leave_type = $other_leave_type;
}
if (empty($leave_type) || empty($date_from) || empty($date_to) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}
$from_date = new DateTime($date_from);
$to_date = new DateTime($date_to);
$today = new DateTime();
if ($from_date < $today) {
    echo json_encode(['success' => false, 'message' => 'Leave start date cannot be in the past']);
    exit;
}
if ($to_date < $from_date) {
    echo json_encode(['success' => false, 'message' => 'Leave end date cannot be earlier than start date']);
    exit;
}
$check_query = "SELECT status FROM leave_requests WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $request_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);
if (!$request = mysqli_fetch_assoc($check_result)) {
    echo json_encode(['success' => false, 'message' => 'Leave request not found']);
    exit;
}
if ($request['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Cannot edit a request that has already been processed']);
    exit;
}
$days_requested = $from_date->diff($to_date)->days + 1;
$update_query = "UPDATE leave_requests 
                SET leave_type = ?, date_from = ?, date_to = ?, days_requested = ?, reason = ?
                WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "sssissi", $leave_type, $date_from, $date_to, $days_requested, $reason, $request_id, $_SESSION['user_id']);
if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Leave request updated successfully!',
        'data' => [
            'leave_type' => $leave_type,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'days_requested' => $days_requested,
            'reason' => $reason
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating leave request. Please try again.']);
}
?>
