<?php
// export_promotions.php
session_start();
require '../db.php';

// Only allow admin/teacher
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','teacher'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch all promotion logs
$query = "SELECT 
            pl.*, 
            u.fullname as student_name,
            u.username as student_username,
            u.class as current_class
          FROM promotion_logs pl
          JOIN users u ON pl.student_id = u.id
          ORDER BY pl.promoted_at DESC";
$result = $conn->query($query);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="promotion_history_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Excel header
echo "DHPS School - Promotion History\n";
echo "Exported on: " . date('d-M-Y h:i A') . "\n\n";

echo "S.No\tStudent ID\tStudent Name\tUsername\tFrom Class\tTo Class\tPercentage\tPromoted By\tPromotion Date\tCurrent Class\n";

$serial = 1;
while ($row = $result->fetch_assoc()) {
    echo $serial++ . "\t";
    echo $row['student_id'] . "\t";
    echo htmlspecialchars($row['student_name']) . "\t";
    echo htmlspecialchars($row['student_username']) . "\t";
    echo $row['from_class'] . "\t";
    echo $row['to_class'] . "\t";
    echo number_format($row['overall_percentage'], 2) . "%\t";
    echo htmlspecialchars($row['promoted_by']) . "\t";
    echo date('d-M-Y', strtotime($row['promoted_at'])) . "\t";
    echo $row['current_class'] . "\n";
}
?>