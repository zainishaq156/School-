<?php
session_start();
require '../db.php';

// Only allow admin/teacher
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','teacher'])) {
    header('Location: ../login.php');
    exit;
}

$userRole = $_SESSION['role'];
$dashboardLink = "dashboard.php";

$classes = ['PG','Nursery','Prep','1','2','3','4','5','6','7','8'];
$selected_class = $_GET['class'] ?? $classes[0];
$today = date('Y-m-d');

// Attendance handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $date = $_POST['date'];
    foreach ($_POST['status'] as $student_id => $status) {
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND date=?");
        $stmt->bind_param("is", $student_id, $date);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt_up = $conn->prepare("UPDATE attendance SET status=? WHERE student_id=? AND date=?");
            $stmt_up->bind_param("sis", $status, $student_id, $date);
            $stmt_up->execute();
        } else {
            $stmt_in = $conn->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
            $stmt_in->bind_param("iss", $student_id, $date, $status);
            $stmt_in->execute();
        }
    }
    $success = "Attendance marked for $date!";
}

// Get students of selected class
$stmt = $conn->prepare("SELECT id, fullname, username FROM users WHERE role='student' AND class=? ORDER BY fullname");
$stmt->bind_param("s", $selected_class);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get today's attendance
$attendance_today = [];
$stmt2 = $conn->prepare("SELECT student_id, status FROM attendance WHERE date=? AND student_id IN (SELECT id FROM users WHERE class=? AND role='student')");
$stmt2->bind_param("ss", $today, $selected_class);
$stmt2->execute();
$res2 = $stmt2->get_result();
while($row = $res2->fetch_assoc()) {
    $attendance_today[$row['student_id']] = $row['status'];
}

// Attendance percentage
$percentages = [];
foreach ($students as $s) {
    $sid = $s['id'];
    $q1 = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE student_id=$sid AND status IN ('Present','Absent')");
    $q2 = $conn->query("SELECT COUNT(*) AS present FROM attendance WHERE student_id=$sid AND status='Present'");
    $total = ($q1 && $q1->num_rows) ? $q1->fetch_assoc()['total'] : 0;
    $present = ($q2 && $q2->num_rows) ? $q2->fetch_assoc()['present'] : 0;
    $percentages[$sid] = $total ? round($present / $total * 100, 1) : 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance - Admin/Teacher Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1849a6;
            --accent: #21b573;
            --danger: #ea4444;
            --light: #f6f8fa;
            --yellow: #ffe7ba;
        }
        html, body { background: var(--light); }
        body { font-family: 'Roboto', Arial, sans-serif; }
        .attendance-card {
            max-width: 1050px;
            margin: 38px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 28px #4471be19;
            padding: 35px 16px 30px 16px;
            position: relative;
        }
        h2 {
            color: var(--primary);
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: .5px;
            margin-bottom: 22px;
        }
        @media (max-width: 900px) {
            .attendance-card { padding: 18px 2vw 14px 2vw; }
            h2 { font-size: 1.3em;}
        }
        @media (max-width: 600px) {
            .attendance-card { border-radius: 0; }
            .submit-btn { width: 100%; }
            h2 { font-size: 1.1em; }
        }
        .success {
            color: #148239;
            background: #e6faef;
            border-radius: 7px;
            padding: 11px 16px;
            margin-bottom: 16px;
            font-weight: 600;
            box-shadow: 0 1px 7px #1cb86b0e;
            font-size: 1.08em;
        }
        .class-select, .date-select {
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        label { font-weight: 700; color: var(--primary); }
        .form-select, .form-control {
            max-width: 220px;
            min-width: 110px;
            border-radius: 9px;
        }
        .date-select { gap: 8px; }
        .submit-btn {
            background: linear-gradient(90deg, var(--primary) 60%, var(--accent) 100%);
            color: #fff;
            border: none;
            border-radius: 11px;
            font-weight: 700;
            padding: 12px 35px;
            font-size: 1.08em;
            margin-top: 8px;
            box-shadow: 0 2px 14px #1849a625;
            transition: background 0.17s, transform .14s;
        }
        .submit-btn:hover, .submit-btn:focus {
            background: linear-gradient(90deg, #122d60 60%, #169c52 100%);
            color: #fff;
            transform: translateY(-1px) scale(1.03);
        }
        .table-wrap { overflow-x: auto; margin-bottom: 16px;}
        table.att-table {
            width: 100%;
            min-width: 670px;
            border-collapse: collapse;
            background: #fcfcff;
            border-radius: 10px;
        }
        th, td {
            padding: 11px 7px;
            border: 1px solid #e6ecf5;
            text-align: center;
            vertical-align: middle;
            font-size: 1em;
        }
        th {
            background: #eaf3ff;
            color: #1749a7;
            font-weight: 700;
            font-size: 1.03em;
        }
        tr:nth-child(even) td { background: #f7fbff; }
        /* Radios - Touch and Colorful */
        .form-check-input[type="radio"] {
            width: 1.5em; height: 1.5em;
            border: 2.5px solid #a6bce6;
            background: #fff;
            margin: 0 4px;
            box-shadow: none;
            transition: box-shadow .13s;
        }
        .form-check-input:checked[type="radio"].present { background-color: #21b573; border-color: #199157;}
        .form-check-input:checked[type="radio"].absent { background-color: #ea4444; border-color: #c60000;}
        .form-check-input:checked[type="radio"].leave { background-color: #ffcf53; border-color: #cc9900;}
        .form-check-input:focus { outline: 2px solid #1849a6; box-shadow: 0 0 7px #21b57360;}
        .attendance-pct { font-weight: 700; color: #21b573; font-size: 1.05em;}
        .back-link {
            color:var(--primary);
            font-weight: 600;
            font-size: 1.07em;
            margin-top: 15px;
            text-decoration: none;
            transition: color .12s;
        }
        .back-link:hover { color: #21b573;}
        /* Accessibility */
        .sr-only { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
    </style>
</head>
<body>
    <div class="attendance-card shadow">
        <h2 class="mb-2">
            <span class="me-1">📅</span> Attendance
            <span class="fs-6 fw-normal text-muted ms-1">(<?= ucfirst($userRole) ?>)</span>
        </h2>
        <?php if (isset($success)): ?><div class="success"><?= $success ?></div><?php endif; ?>

        <!-- Class Filter -->
        <form method="get" class="class-select mb-3">
            <label for="class" class="me-2 mb-0">Class:</label>
            <select name="class" id="class" class="form-select" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c ?>" <?= $selected_class == $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Attendance Table/Form -->
        <form method="post" autocomplete="off">
            <div class="date-select mb-3">
                <label for="date" class="mb-1">Date:</label>
                <input type="date" name="date" id="date" value="<?= $today ?>" required class="form-control">
            </div>
            <div class="table-wrap">
                <table class="att-table table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Username</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i=1; foreach($students as $s): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($s['fullname']) ?></td>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <td>
                                <div class="form-check d-flex justify-content-center">
                                    <input type="radio" class="form-check-input present"
                                           id="present-<?= $s['id'] ?>" name="status[<?= $s['id'] ?>]"
                                           value="Present"
                                           <?= (!isset($attendance_today[$s['id']]) || $attendance_today[$s['id']]=='Present') ? 'checked' : '' ?>
                                           aria-label="Present for <?= htmlspecialchars($s['fullname']) ?>">
                                </div>
                            </td>
                            <td>
                                <div class="form-check d-flex justify-content-center">
                                    <input type="radio" class="form-check-input absent"
                                           id="absent-<?= $s['id'] ?>" name="status[<?= $s['id'] ?>]"
                                           value="Absent"
                                           <?= (isset($attendance_today[$s['id']]) && $attendance_today[$s['id']]=='Absent') ? 'checked' : '' ?>
                                           aria-label="Absent for <?= htmlspecialchars($s['fullname']) ?>">
                                </div>
                            </td>
                            <td>
                                <div class="form-check d-flex justify-content-center">
                                    <input type="radio" class="form-check-input leave"
                                           id="leave-<?= $s['id'] ?>" name="status[<?= $s['id'] ?>]"
                                           value="Leave"
                                           <?= (isset($attendance_today[$s['id']]) && $attendance_today[$s['id']]=='Leave') ? 'checked' : '' ?>
                                           aria-label="Leave for <?= htmlspecialchars($s['fullname']) ?>">
                                </div>
                            </td>
                            <td class="attendance-pct"><?= is_numeric($percentages[$s['id']]) ? $percentages[$s['id']] . "%" : "N/A" ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($students)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No students in this class.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button class="submit-btn mt-3 mb-1" name="mark_attendance" type="submit">Save Attendance</button>
        </form>
        <a href="<?= $dashboardLink ?>" class="back-link d-inline-block">&larr; Back to Dashboard</a>
    </div>
</body>
</html>
