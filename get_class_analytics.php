<?php
session_start();
require '../db.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get all classes
$classes_query = $conn->query("SELECT DISTINCT class FROM users WHERE role='student' ORDER BY class");
$classes = [];
while ($row = $classes_query->fetch_assoc()) {
    $classes[] = $row['class'];
}

$result = [];
foreach ($classes as $class) {
    // Count students in class
    $student_count_query = $conn->query("
        SELECT COUNT(*) as student_count 
        FROM users 
        WHERE role='student' 
        AND class = '" . $conn->real_escape_string($class) . "'
    ");
    $student_count = $student_count_query->fetch_assoc()['student_count'];
    
    // Get fee data for class
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
                END)) as total_balance
        FROM fees f 
        LEFT JOIN fee_payments fp ON f.id = fp.fee_id 
        JOIN users u ON f.student_id = u.id 
        WHERE f.year = $year 
        AND u.class = '" . $conn->real_escape_string($class) . "'
        AND (f.fee_type IS NULL OR f.fee_type = 'regular')
    ");
    
    $data = $query->fetch_assoc();
    $result[] = array_merge([
        'class' => $class,
        'student_count' => $student_count
    ], $data);
}

header('Content-Type: application/json');
echo json_encode($result);
?>