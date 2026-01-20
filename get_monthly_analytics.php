<?php
session_start();
require '../db.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$months = ["January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"];

$result = [];
foreach ($months as $month) {
    $query = $conn->query("
        SELECT 
            COUNT(*) as total_records,
            SUM(COALESCE(fp.total_amount, f.fee_amount)) as total_amount,
            SUM(COALESCE(fp.received_amount, 
                CASE 
                    WHEN f.status = 'Paid' THEN COALESCE(fp.total_amount, f.fee_amount)
                    WHEN f.status = 'Partially Paid' THEN COALESCE(fp.received_amount, 0)
                    ELSE 0 
                END)) as total_received,
            SUM(COALESCE(fp.balance,
                CASE 
                    WHEN f.status = 'Paid' THEN 0
                    WHEN f.status = 'Partially Paid' THEN COALESCE(fp.balance, f.fee_amount - COALESCE(fp.received_amount, 0))
                    ELSE COALESCE(fp.total_amount, f.fee_amount)
                END)) as total_balance,
            SUM(CASE WHEN f.status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN f.status = 'Partially Paid' THEN 1 ELSE 0 END) as partial_count,
            SUM(CASE WHEN f.status = 'Not Paid' THEN 1 ELSE 0 END) as unpaid_count
        FROM fees f 
        LEFT JOIN fee_payments fp ON f.id = fp.fee_id 
        WHERE f.year = $year 
        AND f.month = '" . $conn->real_escape_string($month) . "'
        AND (f.fee_type IS NULL OR f.fee_type = 'regular')
    ");
    
    $data = $query->fetch_assoc();
    $result[] = array_merge(['month' => $month], $data);
}

header('Content-Type: application/json');
echo json_encode($result);
?>