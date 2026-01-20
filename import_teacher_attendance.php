<?php
session_start();
require '../db.php';

// Feedback message
$msg = "";

// Handle CSV Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $handle = fopen($_FILES['file']['tmp_name'], "r");
    $row = 0; $imported = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // Skip header
        // CSV columns: [UserID, Date, Time, Status]
        $userID = trim($data[0]);
        $date = trim($data[1]);
        $time = trim($data[2]);
        $status = trim($data[3]) ?: 'Present';

        // Find teacher by username
        $q = $conn->query("SELECT id FROM users WHERE username='$userID' AND role='teacher'");
        if ($q && $t = $q->fetch_assoc()) {
            $teacher_id = $t['id'];
            $stmt = $conn->prepare("INSERT IGNORE INTO teacher_attendance (teacher_id, date, time, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isss', $teacher_id, $date, $time, $status);
            if ($stmt->execute()) $imported++;
        }
    }
    fclose($handle);
    $msg = "<div class='alert alert-success'>$imported attendance records imported.</div>";
}

// Fetch latest teacher attendance (most recent 50 for display)
$records = [];
$res = $conn->query("
    SELECT ta.*, u.fullname 
    FROM teacher_attendance ta 
    LEFT JOIN users u ON ta.teacher_id = u.id 
    ORDER BY ta.date DESC, ta.time DESC 
    LIMIT 50
");
if ($res) $records = $res->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Teacher Attendance (CSV)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6fafd; }
        .attendance-container { max-width: 900px; margin: 32px auto; background: #fff; border-radius: 13px; box-shadow: 0 2px 16px #e5eefe; padding: 34px 24px; }
        h2 { color: #1b407a; font-weight: bold; }
        .import-box { background: #f9f9ff; padding: 16px 16px 10px 16px; border-radius: 8px; margin-bottom: 24px; }
        .attendance-table th, .attendance-table td { font-size: 1em; text-align: center; vertical-align: middle; }
        .attendance-table th { background: #e8f2fe; color: #1b407a; }
        .badge-status { font-size: 1em; padding: 6px 14px; border-radius: 13px; }
        .badge-present { background: #d2fbe2; color: #118b36; }
        .badge-late    { background: #fff0c6; color: #a86a02; }
        .badge-absent  { background: #ffdada; color: #ce0d1a; }
        .badge-other   { background: #f3edff; color: #6c30ab; }
        @media (max-width: 600px) {
            .attendance-container { padding: 10px 2vw;}
            .attendance-table th, .attendance-table td { font-size: .92em;}
        }
    </style>
</head>
<body>
<div class="attendance-container">
    <h2 class="mb-3">Import Teacher Attendance (CSV)</h2>
    <?= $msg ?>
    <div class="import-box mb-3">
        <form class="row g-2 align-items-center" method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="col-12 col-sm-8">
                <input type="file" name="file" class="form-control" accept=".csv" required>
            </div>
            <div class="col-12 col-sm-4 mt-2 mt-sm-0">
                <input type="submit" class="btn btn-primary w-100" value="Import CSV">
            </div>
        </form>
        <div class="form-text mt-1">CSV Format: <b>UserID,Date,Time,Status</b></div>
    </div>
    <h3 class="mt-4 mb-3">Recent Teacher Attendance Records</h3>
    <div class="table-responsive">
    <table class="attendance-table table table-hover align-middle">
        <thead>
        <tr>
            <th>#</th>
            <th>Teacher</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="5" style="text-align:center;">No attendance records yet.</td></tr>
        <?php else:
            $i=1;
            foreach($records as $r):
                $badgeClass = 'badge-other';
                if (strcasecmp($r['status'], 'Present')==0) $badgeClass='badge-present';
                elseif (strcasecmp($r['status'],'Late')==0) $badgeClass='badge-late';
                elseif (strcasecmp($r['status'],'Absent')==0) $badgeClass='badge-absent';
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($r['fullname'] ?? $r['teacher_id']) ?></td>
                <td><?= htmlspecialchars($r['date']) ?></td>
                <td><?= htmlspecialchars($r['time'] ?: '-') ?></td>
                <td><span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    <a href="dashboard.php" class="btn btn-link mt-3" style="color:#2b55c0;">&larr; Back to Dashboard</a>
</div>
</body>
</html>
