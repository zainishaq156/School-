<?php
// auto_promote.php
// This script can be run as a cron job to automatically promote eligible students
require 'db.php';

// Function to check and promote student (same as in marks.php)
function checkAndPromoteStudent($conn, $student_id) {
    // ... (same function as in marks.php)
}

// Get all students
$query = "SELECT id, fullname, class FROM users WHERE role = 'student' ORDER BY class";
$result = $conn->query($query);
$students = $result->fetch_all(MYSQLI_ASSOC);

$promoted_count = 0;
$log = [];

foreach ($students as $student) {
    $promotion_result = checkAndPromoteStudent($conn, $student['id']);
    
    if ($promotion_result['promoted']) {
        $promoted_count++;
        $log[] = "✅ Promoted: {$promotion_result['student_name']} from {$promotion_result['from_class']} to {$promotion_result['to_class']}";
    }
}

// Log results
echo "<h2>Auto Promotion Results</h2>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Total students checked: " . count($students) . "</p>";
echo "<p>Students promoted: " . $promoted_count . "</p>";
echo "<hr>";
echo "<h3>Promotion Log:</h3>";
foreach ($log as $entry) {
    echo "<p>" . $entry . "</p>";
}

// Save log to file
$log_content = date('Y-m-d H:i:s') . " - Promoted $promoted_count students\n" . implode("\n", $log);
file_put_contents('promotion_log.txt', $log_content . "\n\n", FILE_APPEND);
?>