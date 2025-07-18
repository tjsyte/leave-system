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
    echo json_encode(['success' => false, 'message' => 'Cannot delete a request that has already been processed']);
    exit;
}
$delete_query = "DELETE FROM leave_requests WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($stmt, "ii", $request_id, $_SESSION['user_id']);
if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Leave request deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting leave request']);
}
?>
