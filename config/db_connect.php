<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "leave_system";
$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}
function check_manager() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'manager') {
        header("Location: ../employee/dashboard.php");
        exit();
    }
}
function format_date($date) {
    return date('M d, Y', strtotime($date));
}
function calculate_days($date_from, $date_to) {
    $from = new DateTime($date_from);
    $to = new DateTime($date_to);
    $interval = $from->diff($to);
    return $interval->days + 1;
}
?>
