<?php
// check_promotion.php
session_start();
require '../db.php';

// Only allow admin/teacher
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','teacher'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$student_id = intval($_GET['student_id'] ?? 0);

if ($student_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid student ID']);
    exit;
}

// Function to check promotion eligibility (same as in marks.php)
function checkPromotionEligibility($conn, $student_id) {
    // Get student's current class
    $student_stmt = $conn->prepare("SELECT class FROM users WHERE id = ? AND role = 'student'");
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        return ['eligible' => false, 'reason' => 'Student not found'];
    }
    
    $student = $student_result->fetch_assoc();
    $current_class = $student['class'];
    
    // Define class progression order
    $class_order = ['PG', 'Nursery', 'Prep', '1', '2', '3', '4', '5', '6', '7', '8'];
    
    // Check if student is already in the highest class
    $current_index = array_search($current_class, $class_order);
    if ($current_index === false || $current_index >= count($class_order) - 1) {
        return ['eligible' => false, 'reason' => 'Student is already in the highest class'];
    }
    
    // Get final exam marks
    $marks_stmt = $conn->prepare("
        SELECT subject, percentage 
        FROM marks 
        WHERE student_id = ? 
        AND (exam_name LIKE '%Final%' OR exam_name LIKE '%Annual%')
    ");
    $marks_stmt->bind_param("i", $student_id);
    $marks_stmt->execute();
    $marks_result = $marks_stmt->get_result();
    $final_marks = $marks_result->fetch_all(MYSQLI_ASSOC);
    
    if (empty($final_marks)) {
        return ['eligible' => false, 'reason' => 'No final exam marks found'];
    }
    
    // Check all subjects have 40% or more
    $passed_all = true;
    $total_percentage = 0;
    $failed_subjects = [];
    
    foreach ($final_marks as $mark) {
        $percentage = floatval($mark['percentage']);
        if ($percentage < 40) {
            $passed_all = false;
            $failed_subjects[] = $mark['subject'] . ' (' . number_format($percentage, 1) . '%)';
        }
        $total_percentage += $percentage;
    }
    
    $overall_percentage = count($final_marks) > 0 ? ($total_percentage / count($final_marks)) : 0;
    
    if ($passed_all && $overall_percentage >= 40) {
        return [
            'eligible' => true,
            'from_class' => $current_class,
            'to_class' => $class_order[$current_index + 1],
            'overall_percentage' => $overall_percentage,
            'subject_count' => count($final_marks)
        ];
    } else {
        return [
            'eligible' => false,
            'reason' => 'Failed in some subjects or overall percentage below 40%',
            'failed_subjects' => $failed_subjects,
            'overall_percentage' => $overall_percentage
        ];
    }
}

// Check promotion eligibility
$result = checkPromotionEligibility($conn, $student_id);

header('Content-Type: application/json');
echo json_encode($result);
?>