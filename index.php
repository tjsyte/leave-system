<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'manager') {
        header("Location: manager/dashboard.php");
    } else {
        header("Location: employee/dashboard.php");
    }
    exit();
}
header("Location: auth/login.php");
exit();
?>
