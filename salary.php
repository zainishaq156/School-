<?php
session_start();
require '../db.php';

// Only allow teachers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$teacher = $conn->query("SELECT * FROM users WHERE username='$username' LIMIT 1")->fetch_assoc();
$teacherName = $teacher['fullname'] ?? $teacher['username'];
$teacherEmail = $teacher['email'] ?? '';
$designation = "Teacher";

// Ensure a staff record exists for this teacher
$staff = $conn->query("SELECT * FROM staff WHERE username='$username' LIMIT 1")->fetch_assoc();
if (!$staff) {
    $stmt = $conn->prepare("INSERT INTO staff (fullname, designation, username) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $teacherName, $designation, $username);
    $stmt->execute();
    $staff_id = $stmt->insert_id;
} else {
    $staff_id = $staff['id'];
}

// Fetch salary records
$salary_records = [];
if ($staff_id) {
    $salary_records = $conn->query("SELECT * FROM staff_salary WHERE staff_id=$staff_id ORDER BY year DESC, month DESC, date_paid DESC")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Salary - Teacher Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f7fafc; font-family: 'Roboto', Arial, sans-serif; }
        .salary-wrap { max-width: 700px; margin: 35px auto; background: #fff; border-radius: 15px; box-shadow: 0 2px 18px #e3ecfa70; padding: 22px 7px; }
        .table thead { background: #f1f6fb; }
        .salary-head { color: #12499a; font-weight: 700; }
        @media (max-width:800px){ .salary-wrap{ padding:10px 2vw; } }
        @media (max-width:500px){ .salary-wrap{ padding:4px 1vw; } }
    </style>
</head>
<body>
<div class="salary-wrap">
    <h2 class="mb-3 salary-head"><i class="bi bi-wallet2"></i> My Salary History</h2>
    <div class="mb-2"><strong><?= htmlspecialchars($teacherName) ?></strong><br>
        <small><?= htmlspecialchars($teacherEmail) ?></small>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle text-center">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Amount (Rs)</th>
                    <th>Date Paid</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($salary_records)): ?>
                <tr><td colspan="6" class="text-muted">No salary record found.</td></tr>
            <?php else: $i=1; foreach ($salary_records as $sal): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($sal['month']) ?></td>
                    <td><?= htmlspecialchars($sal['year']) ?></td>
                    <td><?= number_format($sal['salary_amount'],2) ?></td>
                    <td><?= htmlspecialchars($sal['date_paid']) ?></td>
                    <td><?= htmlspecialchars($sal['remarks']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="text-center mt-4">
    <a href="dashboard.php" class="btn btn-outline-primary btn-lg" style="font-weight:600;">
        <i class="bi bi-arrow-left-circle"></i> Back to Dashboard
    </a>
</div>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
