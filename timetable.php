<?php
session_start();
require '../db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php'); exit;
}
$class = $_SESSION['class'] ?? '';
if (!$class) {
    $user = $conn->query("SELECT class FROM users WHERE username='{$_SESSION['username']}'")->fetch_assoc();
    $class = $user['class'] ?? '';
}
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
// Build timetable matrix [day][period]
$matrix = [];
$result = $conn->query("SELECT * FROM timetable WHERE class='$class' ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), period");
while ($row = $result->fetch_assoc()) {
    $matrix[$row['day']][$row['period']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Timetable</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background: #f6fafd; font-family: 'Segoe UI', Arial, sans-serif; }
        .tt-wrap {
            max-width: 900px;
            margin: 32px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px #a5c9fc2a;
            padding: 34px 18px 28px 18px;
        }
        h2 { color: #2446a6; margin-bottom: 26px; text-align: center; font-weight: 700; }
        .tt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.06em;
        }
        .tt-table th, .tt-table td {
            border: 1px solid #e1eaf5;
            padding: 9px 6px;
            vertical-align: top;
            text-align: center;
        }
        .tt-table th {
            background: #e8f2fe;
            color: #2446a6;
            font-weight: 600;
        }
        .tt-table td {
            background: #fcfdff;
            min-width: 80px;
            font-size: 0.97em;
            line-height: 1.45em;
        }
        .subject-cell b {
            color: #176eff;
            font-size: 1em;
        }
        .subject-cell span.teacher {
            color: #6a8ab7;
            font-size: 0.93em;
            display: block;
        }
        .subject-cell span.time {
            color: #888;
            font-size: 0.91em;
            display: block;
        }
        .back-btn {
            display: inline-block;
            background: #437ef7;
            color: #fff;
            font-weight: 600;
            padding: 10px 34px;
            border-radius: 7px;
            text-decoration: none;
            margin: 22px auto 0 auto;
            transition: background 0.16s;
        }
        .back-btn:hover { background: #2446a6; color: #fff; }
        @media (max-width: 800px) {
            .tt-wrap { padding: 11px 2vw 15px 2vw; }
            .tt-table th, .tt-table td { font-size: 0.97em; padding: 7px 2px; }
        }
        @media (max-width: 576px) {
            .tt-wrap { padding: 4px 1vw 7px 1vw; border-radius: 6px; }
            h2 { font-size: 1.05em; }
            .tt-table { font-size: 0.93em; }
            .tt-table th, .tt-table td { padding: 5px 1px; }
            .back-btn { width: 100%; font-size: 1em; }
        }
        /* Optional: horizontal scroll for wide tables on mobile */
        .tt-table-responsive {
            width: 100%;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="tt-wrap shadow">
    <h2>Your Class Timetable</h2>
    <div class="tt-table-responsive">
        <table class="tt-table">
            <tr>
                <th>Day / Period</th>
                <?php for($p=1; $p<=8; $p++): ?>
                    <th>Period <?= $p ?></th>
                <?php endfor; ?>
            </tr>
            <?php foreach($days as $day): ?>
                <tr>
                    <th><?= $day ?></th>
                    <?php for($p=1; $p<=8; $p++): ?>
                        <td class="subject-cell">
                            <?php
                            if(isset($matrix[$day][$p])) {
                                echo "<b>".htmlspecialchars($matrix[$day][$p]['subject'])."</b>";
                                echo "<span class='teacher'>".htmlspecialchars($matrix[$day][$p]['teacher'])."</span>";
                                if ($matrix[$day][$p]['start_time'] && $matrix[$day][$p]['end_time']) {
                                    echo "<span class='time'>".htmlspecialchars($matrix[$day][$p]['start_time'])." - ".htmlspecialchars($matrix[$day][$p]['end_time'])."</span>";
                                }
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="text-center">
        <a href="dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
    </div>
</div>
</body>
</html>
