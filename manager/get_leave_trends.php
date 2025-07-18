<?php
session_start();
require_once '../config/db_connect.php';
check_login();
check_manager();
$query = "SELECT 
    DATE_FORMAT(filed_date, '%Y-%m') as month,
    COUNT(*) as count
FROM leave_requests 
WHERE filed_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(filed_date, '%Y-%m')
ORDER BY month ASC";
$result = mysqli_query($conn, $query);
$labels = [];
$values = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $labels[] = date('M Y', strtotime("-$i months"));
    $values[] = 0; 
}
while ($row = mysqli_fetch_assoc($result)) {
    $monthIndex = array_search(date('M Y', strtotime($row['month'] . '-01')), $labels);
    if ($monthIndex !== false) {
        $values[$monthIndex] = (int)$row['count'];
    }
}
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'values' => $values
]);
?>
