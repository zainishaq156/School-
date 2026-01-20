<?php
session_start();
require '../db.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$result = [];

// Get Paid status data
$paid_query = $conn->query("
    SELECT 
        COUNT(*) as count,
        SUM(COALESCE(fp.total_amount, f.fee_amount)) as amount
    FROM fees f 
    LEFT JOIN fee_payments fp ON f.id = fp.fee_id 
    WHERE f.year = $year 
    AND f.status = 'Paid'
    AND (f.fee_type IS NULL OR f.fee_type = 'regular')
");
$paid_data = $paid_query->fetch_assoc();
$result['Paid'] = $paid_data;

// Get Partially Paid status data
$partial_query = $conn->query("
    SELECT 
        COUNT(*) as count,
        SUM(COALESCE(fp.total_amount, f.fee_amount)) as amount
    FROM fees f 
    LEFT JOIN fee_payments fp ON f.id = fp.fee_id 
    WHERE f.year = $year 
    AND f.status = 'Partially Paid'
    AND (f.fee_type IS NULL OR f.fee_type = 'regular')
");
$partial_data = $partial_query->fetch_assoc();
$result['Partially Paid'] = $partial_data;

// Get Not Paid status data
$unpaid_query = $conn->query("
    SELECT 
        COUNT(*) as count,
        SUM(COALESCE(fp.total_amount, f.fee_amount)) as amount
    FROM fees f 
    LEFT JOIN fee_payments fp ON f.id = fp.fee_id 
    WHERE f.year = $year 
    AND f.status = 'Not Paid'
    AND (f.fee_type IS NULL OR f.fee_type = 'regular')
");
$unpaid_data = $unpaid_query->fetch_assoc();
$result['Not Paid'] = $unpaid_data;

header('Content-Type: application/json');
echo json_encode($result);
?>