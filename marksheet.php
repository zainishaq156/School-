<?php
session_start();
require '../db.php'; // Your database connection file

// Get student_id from URL
$student_id = (int)($_GET['student_id'] ?? 0);
if (!$student_id) {
    exit('Invalid student ID!');
}

// Authorization check
$role = $_SESSION['role'] ?? '';
$current_user_id = $_SESSION['id'] ?? 0;

if ($role !== 'admin' && $role !== 'teacher' && ($role !== 'student' || $student_id !== $current_user_id)) {
    exit('Access denied. You are not authorized to view this marksheet.');
}

// ================ DATABASE QUERY: Fetch student details including father_name ================
$stmt = $conn->prepare("SELECT fullname, father_name, username, class 
                        FROM users 
                        WHERE id = ? AND role = 'student' 
                        LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('Student not found.');
}

$stu = $result->fetch_assoc();
$stmt->close();
// =========================================================================================

// ================ DATABASE QUERY: Fetch all marks for this student =========================
$marks_stmt = $conn->prepare("SELECT subject, exam_name, marks_obtained, total_marks, date, remarks 
                              FROM marks 
                              WHERE student_id = ? 
                              ORDER BY subject ASC, exam_name ASC, date DESC");
$marks_stmt->bind_param("i", $student_id);
$marks_stmt->execute();
$marks_result = $marks_stmt->get_result();

$marks = $marks_result->fetch_all(MYSQLI_ASSOC);
$marks_stmt->close();
// =========================================================================================

// Calculate total marks and percentage
$total_obt = $total_max = 0;
$pass_percentage = 40;

foreach ($marks as $m) {
    $total_obt += $m['marks_obtained'];
    $total_max += $m['total_marks'];
}

$overall_percent = $total_max > 0 ? round(($total_obt / $total_max) * 100, 2) : 0;
$overall_status = ($overall_percent >= $pass_percentage && !empty($marks)) ? "PASS" : "FAIL";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Marksheet - <?= htmlspecialchars($stu['fullname']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f0f4f8;
            font-family: Arial, sans-serif;
        }

        .marksheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .marksheet-a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 30px 25px;
            box-sizing: border-box;
            background: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        @media print {
            body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            .marksheet-container { box-shadow: none !important; border-radius: 0 !important; }
            .marksheet-a4 {
                width: 210mm !important;
                height: 297mm !important;
                padding: 30px 25px !important;
                margin: 0 !important;
            }
            .print-btn { display: none !important; }
            @page { size: A4; margin: 0; }
        }

        .marksheet-head { text-align: center; margin-bottom: 20px; }
        .marksheet-logo { width: 1.5in; height: auto; display: block; margin: 0 auto 10px auto; }
        .school-title { font-size: 1.7em; font-weight: bold; color: #16a34a; letter-spacing: 1.8px; margin: 8px 0; }
        .marksheet-title { font-size: 1.2em; font-weight: bold; color: #1b407a; margin: 8px 0; }
        
        .info-row {
            font-size: 1.15em;
            color: #323a4b;
            margin: 15px 0;
            line-height: 1.7;
        }

        .marks-table-wrapper { position: relative; width: 100%; margin: 20px 0; }
        .watermark-dhps {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-19deg);
            font-size: 10vw; font-weight: bold; color: rgba(110,156,211,0.12);
            letter-spacing: 0.3em; pointer-events: none; z-index: 1; user-select: none;
        }

        .marks-table {
            border-collapse: collapse; width: 100%; background: #fdfdff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); position: relative; z-index: 2;
        }
        .marks-table th, .marks-table td {
            border: 1.2px solid #c0d3e7; padding: 10px 8px; font-size: 1.02em; text-align: center;
        }
        .marks-table th { background: #e7f2fd; color: #2446a6; font-weight: 600; }
        .remarks-cell { text-align: left; max-width: 150px; word-wrap: break-word; white-space: normal; }
        .percent-cell { font-weight: bold; color: #1b407a; }
        .status-pass { color: #169c3c; font-weight: bold; }
        .status-lessthan40 { color: #e62613; font-weight: bold; }

        .total-section { text-align: right; font-size: 1.12em; margin: 20px 0 10px 0; }
        .overall-status { text-align: right; font-size: 1.2em; font-weight: bold; margin: 10px 0; }

        .signature-stamp-row {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd;
        }
        .signature-block, .stamp-block { width: 200px; text-align: center; }
        .signature-line { border-top: 1.5px solid #333; width: 160px; margin: 20px auto 0 auto; }
        .stamp-box { border: 2px dashed #16a34a; width: 110px; height: 60px; border-radius: 10px; margin: 20px auto 0 auto; }

        .print-btn {
            display: block; width: 220px; margin: 40px auto 20px auto;
            padding: 14px 20px; background: #2446a6; color: #fff; font-size: 1.2em;
            font-weight: 600; border: none; border-radius: 10px; cursor: pointer;
            box-shadow: 0 4px 10px rgba(36,70,166,0.3);
        }
        .print-btn:hover { background: #1e3a8a; }
    </style>
</head>
<body>

<div class="marksheet-container">
    <div class="marksheet-a4">
        <div class="marksheet-head">
            <img src="../images/marksheetlogo.png" alt="School Logo" class="marksheet-logo">
            <div class="school-title">DAR UL HUDA PUBLIC SCHOOL SANGHOI</div>
            <div class="marksheet-title">STUDENT MARKSHEET</div>
            <div class="info-row">
                <b>Student Name:</b> <?= htmlspecialchars($stu['fullname']) ?><br>
                <b>Father's Name:</b> <?= htmlspecialchars($stu['father_name'] ?? 'Not Provided') ?><br>
                <b>Class:</b> <?= htmlspecialchars($stu['class']) ?> &nbsp; | &nbsp;
                <b>Student ID:</b> <?= htmlspecialchars($stu['username']) ?>
            </div>
        </div>

        <div class="marks-table-wrapper">
            <div class="watermark-dhps">DHPS</div>
            <table class="marks-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Exam</th>
                        <th>Marks Obtained</th>
                        <th>Total Marks</th>
                        <th>Percentage</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($marks)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 30px; color: #888; font-style: italic;">
                            No marks recorded yet for this student.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($marks as $m): 
                            $percent_this = $m['total_marks'] > 0 ? round(($m['marks_obtained'] / $m['total_marks']) * 100, 2) : 0;
                            $status = $percent_this >= $pass_percentage ? "Pass" : "Less than 40%";
                            $status_class = $percent_this >= $pass_percentage ? "pass" : "lessthan40";
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($m['subject']) ?></td>
                            <td><?= htmlspecialchars($m['exam_name']) ?></td>
                            <td><?= htmlspecialchars($m['marks_obtained']) ?></td>
                            <td><?= htmlspecialchars($m['total_marks']) ?></td>
                            <td class="percent-cell"><?= $percent_this ?>%</td>
                            <td><?= htmlspecialchars($m['date']) ?></td>
                            <td class="status-<?= $status_class ?>"><?= $status ?></td>
                            <td class="remarks-cell"><?= htmlspecialchars($m['remarks'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <b>Total Marks Obtained:</b> <?= $total_obt ?> / <?= $total_max ?><br>
            <b>Overall Percentage:</b> <?= $overall_percent ?>%
        </div>

        <div class="overall-status">
            <b>Final Result:</b>
            <span class="status-<?= strtolower($overall_status) ?>"><?= $overall_status ?></span>
        </div>

        <div class="signature-stamp-row">
            <div class="signature-block">
                <div>Signature of Principal</div>
                <div class="signature-line"></div>
            </div>
            <div class="stamp-block">
                <div>Official School Stamp</div>
                <div class="stamp-box"></div>
            </div>
        </div>

        <button class="print-btn" onclick="window.print()">🖨️ Print Marksheet</button>
    </div>
</div>

</body>
</html>