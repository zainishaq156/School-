<?php
session_start();
require '../db.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = $error = "";

// Check and create percentage column if missing
$checkPercentage = $conn->query("SHOW COLUMNS FROM marks LIKE 'percentage'");
if ($checkPercentage && $checkPercentage->num_rows == 0) {
    $conn->query("ALTER TABLE marks ADD COLUMN percentage DECIMAL(5,2) DEFAULT 0.00 AFTER total_marks");
    $message .= "Added 'percentage' column to marks table.<br>";
}

// Check and create promotion_logs table if missing
$checkPromotionLogs = $conn->query("SHOW TABLES LIKE 'promotion_logs'");
if ($checkPromotionLogs && $checkPromotionLogs->num_rows == 0) {
    $conn->query("
        CREATE TABLE promotion_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            from_class VARCHAR(20) NOT NULL,
            to_class VARCHAR(20) NOT NULL,
            overall_percentage DECIMAL(5,2) NOT NULL,
            promoted_by VARCHAR(100) NOT NULL,
            promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $message .= "Created promotion_logs table.<br>";
}

// Check and add father_name column to users table if missing
$checkFatherName = $conn->query("SHOW COLUMNS FROM users LIKE 'father_name'");
if ($checkFatherName && $checkFatherName->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN father_name VARCHAR(100) AFTER fullname");
    $message .= "Added 'father_name' column to users table.<br>";
}

// Update existing marks to calculate percentage if not set
$updatePercentage = $conn->query("
    UPDATE marks
    SET percentage = ROUND((marks_obtained / total_marks) * 100, 2)
    WHERE percentage = 0 OR percentage IS NULL
");
if ($updatePercentage) {
    $message .= "Updated existing marks with calculated percentages.<br>";
}

if (!$message) {
    $message = "Database is already up to date!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Database Setup Complete</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    <a href="marks.php" class="btn btn-primary">Back to Marks Management</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
