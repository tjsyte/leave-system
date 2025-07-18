<?php
session_start();
require_once '../config/db_connect.php';
check_login();
check_manager();
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $remarks = isset($_POST['remarks']) ? sanitize_input($_POST['remarks']) : '';
    if ($action === 'add_employee') {
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $department = isset($_POST['department']) ? trim($_POST['department']) : '';
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        if (empty($full_name) || empty($email) || empty($department) || empty($username) || empty($password)) {
            $error_message = "All fields are required.";
        } else {
            $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "ss", $username, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_message = "Username or email already exists.";
            } else {
                mysqli_begin_transaction($conn);
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_query = "INSERT INTO users (username, password, role, full_name, email, department) VALUES (?, ?, 'employee', ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $full_name, $email, $department);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to add employee.");
                    }
                    $new_employee_id = mysqli_insert_id($conn);
                    $leave_types_query = "SELECT leave_type, default_balance FROM leave_types WHERE is_active = 1";
                    $leave_types_result = mysqli_query($conn, $leave_types_query);
                    while ($leave_type = mysqli_fetch_assoc($leave_types_result)) {
                        $insert_balance_query = "INSERT INTO leave_balances (user_id, leave_type, total_earned, used, balance) VALUES (?, ?, ?, 0, ?)";
                        $stmt = mysqli_prepare($conn, $insert_balance_query);
                        $default_balance = $leave_type['default_balance'];
                        mysqli_stmt_bind_param($stmt, "isii", $new_employee_id, $leave_type['leave_type'], $default_balance, $default_balance);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Failed to create default leave balance for " . $leave_type['leave_type']);
                        }
                    }
                    mysqli_commit($conn);
                    $success_message = "Employee added successfully with default leave balances.";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error_message = $e->getMessage();
                }
            }
        }
        if (!empty($error_message)) {
            $_SESSION['error_message'] = $error_message;
        } else {
            $_SESSION['success_message'] = $success_message;
        }
        header("Location: employees.php");
        exit();
    }
    if ($action === 'edit_employee') {
        $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $department = isset($_POST['department']) ? trim($_POST['department']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $leave_balances = isset($_POST['leave_balances']) && is_array($_POST['leave_balances']) ? $_POST['leave_balances'] : [];
        if ($employee_id <= 0 || empty($full_name) || empty($email) || empty($department)) {
            $error_message = "Please fill in all required fields.";
        } else {
            $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "si", $email, $employee_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_message = "Email is already used by another user.";
            } else {
                mysqli_begin_transaction($conn);
                try {
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_query = "UPDATE users SET full_name = ?, email = ?, department = ?, password = ? WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $department, $hashed_password, $employee_id);
                    } else {
                        $update_query = "UPDATE users SET full_name = ?, email = ?, department = ? WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $department, $employee_id);
                    }
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to update employee.");
                    }
                    foreach ($leave_balances as $type => $balance_data) {
                        if (is_array($balance_data)) {
                            $total_earned = (int)($balance_data['total_earned'] ?? 0);
                            $used = (int)($balance_data['used'] ?? 0);
                            $balance = (int)($balance_data['balance'] ?? 0);
                        } else {
                            $balance = (int)$balance_data;
                            $total_earned = $balance;
                            $used = 0;
                        }
                        if ($total_earned < 0 || $used < 0 || $balance < 0) {
                            throw new Exception("Invalid leave balance values for $type.");
                        }
                        $check_balance_query = "SELECT id FROM leave_balances WHERE user_id = ? AND leave_type = ?";
                        $stmt = mysqli_prepare($conn, $check_balance_query);
                        mysqli_stmt_bind_param($stmt, "is", $employee_id, $type);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_store_result($stmt);
                        if (mysqli_stmt_num_rows($stmt) > 0) {
                            $update_balance_query = "UPDATE leave_balances SET total_earned = ?, used = ?, balance = ? WHERE user_id = ? AND leave_type = ?";
                            $stmt = mysqli_prepare($conn, $update_balance_query);
                            mysqli_stmt_bind_param($stmt, "iiiis", $total_earned, $used, $balance, $employee_id, $type);
                            if (!mysqli_stmt_execute($stmt)) {
                                throw new Exception("Failed to update leave balance for $type.");
                            }
                        } else {
                            $insert_balance_query = "INSERT INTO leave_balances (user_id, leave_type, total_earned, used, balance) VALUES (?, ?, ?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $insert_balance_query);
                            mysqli_stmt_bind_param($stmt, "isiii", $employee_id, $type, $total_earned, $used, $balance);
                            if (!mysqli_stmt_execute($stmt)) {
                                throw new Exception("Failed to insert leave balance for $type.");
                            }
                        }
                    }
                    mysqli_commit($conn);
                    $success_message = "Employee updated successfully.";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error_message = $e->getMessage();
                }
            }
        }
        if (!empty($error_message)) {
            $_SESSION['error_message'] = $error_message;
        } else {
            $_SESSION['success_message'] = $success_message;
        }
        header("Location: employees.php");
        exit();
    }
    if ($request_id > 0 && ($action === 'approve' || $action === 'reject' || $action === 'approved' || $action === 'rejected')) {
        $check_query = "SELECT lr.*, u.full_name, lb.balance 
                       FROM leave_requests lr 
                       JOIN users u ON lr.user_id = u.id 
                       LEFT JOIN leave_balances lb ON (lr.user_id = lb.user_id AND lr.leave_type = lb.leave_type)
                       WHERE lr.id = ? AND lr.status = 'pending'";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $request_result = mysqli_stmt_get_result($stmt);
        if ($request = mysqli_fetch_assoc($request_result)) {
            $new_status = ($action === 'approve' || $action === 'approved') ? 'approved' : 'rejected';
            if (empty($remarks)) {
                $remarks = ($action === 'approve' || $action === 'approved') ? 'Approved by manager' : 'Rejected by manager';
            }
            if (($action === 'approve' || $action === 'approved') && $request['balance'] !== null && $request['days_requested'] > $request['balance']) {
                $error_message = "Cannot approve: Insufficient leave balance. Employee has {$request['balance']} days available.";
            } else {
                mysqli_begin_transaction($conn);
                try {
                    $update_query = "UPDATE leave_requests 
                                   SET status = ?, manager_remarks = ?, approved_by = ?, approved_date = NOW() 
                                   WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, "ssii", $new_status, $remarks, $_SESSION['user_id'], $request_id);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to update leave request");
                    }
                    if (($action === 'approve' || $action === 'approved') && $request['balance'] !== null) {
                        $balance_update = "UPDATE leave_balances 
                                         SET used = used + ?, balance = balance - ? 
                                         WHERE user_id = ? AND leave_type = ?";
                        $stmt = mysqli_prepare($conn, $balance_update);
                        mysqli_stmt_bind_param($stmt, "iiis", $request['days_requested'], $request['days_requested'], $request['user_id'], $request['leave_type']);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Failed to update leave balance");
                        }
                    }
                    mysqli_commit($conn);
                    $success_message = "Leave request has been {$new_status} successfully.";
                    $_SESSION['success_message'] = $success_message;
                    header("Location: pending_approvals.php");
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error_message = "Error processing request: " . $e->getMessage();
                }
            }
        } else {
            $error_message = "Leave request not found or already processed.";
        }
    } else {
        $error_message = "Invalid request parameters.";
    }
} else {
    $error_message = "Invalid request method.";
}
$_SESSION['error_message'] = $error_message;
header("Location: pending_approvals.php");
exit();
?>
