<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: ../login.php');
    exit;
}

$conn->query("ALTER TABLE marks ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL");

$all_classes = ['PG', 'Nursery', 'Prep', '1', '2', '3', '4', '5', '6', '7', '8'];

if ($_SESSION['role'] === 'admin') {
    $allowed_classes = $all_classes;
    $teacherName = 'Administrator';
    $teacherEmail = '';
    $teacherClass = '';
} else {
    $stmt = $conn->prepare("SELECT class, fullname, email FROM users WHERE username = ? AND role = 'teacher'");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    $stmt->close();

    $teacherName = $teacher['fullname'] ?? $_SESSION['username'];
    $teacherEmail = $teacher['email'] ?? '';
    $allowed_classes = $teacher && !empty($teacher['class']) ? [$teacher['class']] : [];
    $teacherClass = $teacher['class'] ?? '';
    if (empty($allowed_classes)) $message = "No class assigned. Contact admin.";
}

$class_sel = $_GET['class'] ?? ($allowed_classes[0] ?? '');
if (!in_array($class_sel, $allowed_classes)) $class_sel = $allowed_classes[0] ?? '';

$search_term = trim($_GET['student_search'] ?? '');
$message = $message ?? '';

// Handle Promote Student
if (isset($_GET['promote'])) {
    $student_id = intval($_GET['promote']);
    if ($student_id > 0) {
        // Verify student belongs to teacher's class
        $stmt = $conn->prepare("SELECT class FROM users WHERE id=? AND role='student' AND class=?");
        $stmt->bind_param("is", $student_id, $class_sel);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $current_class = $row['class'];
            
            // Check eligibility based on Final Exam aggregate
            $mres = $conn->query("SELECT * FROM marks WHERE student_id=$student_id AND exam_name='Final Exam'");
            $final_marks = $mres->fetch_all(MYSQLI_ASSOC);
            $total_obtained = 0;
            $total_total = 0;
            foreach ($final_marks as $mark) {
                $total_obtained += floatval($mark['marks_obtained']);
                $total_total += floatval($mark['total_marks']);
            }
            
            $agg_perc = 0;
            if ($total_total > 0) {
                $agg_perc = ($total_obtained / $total_total) * 100;
            }
            
            // Check if student is eligible (>=40%)
            $eligible = ($agg_perc >= 40);
            
            if ($eligible) {
                // Get next class
                $class_index = array_search($current_class, $all_classes);
                if ($class_index !== false && isset($all_classes[$class_index + 1])) {
                    $next_class = $all_classes[$class_index + 1];
                    
                    // Update class
                    $up_stmt = $conn->prepare("UPDATE users SET class=? WHERE id=?");
                    $up_stmt->bind_param("si", $next_class, $student_id);
                    if ($up_stmt->execute()) {
                        $message = "Student promoted to $next_class! (Marks: " . number_format($agg_perc, 2) . "%)";
                        // Also update the current class selection
                        if ($next_class !== $class_sel && $_SESSION['role'] === 'teacher') {
                            $class_sel = $next_class;
                        }
                    } else {
                        $message = "Failed to promote student.";
                    }
                    $up_stmt->close();
                } else {
                    $message = "No next class available for promotion.";
                }
            } else {
                if ($total_total == 0) {
                    $message = "Student has no Final Exam marks recorded.";
                } else {
                    $message = "Student not eligible for promotion (Final Exam aggregate: " . number_format($agg_perc, 2) . "% < 40%).";
                }
            }
        } else {
            $message = "Student not found in your class.";
        }
        $stmt->close();
    }
    header('Location: marks.php?class=' . urlencode($class_sel) . '&student_search=' . urlencode($search_term));
    exit;
}

if (isset($_GET['delete_mark'])) {
    $mark_id = intval($_GET['delete_mark']);
    if ($mark_id > 0) {
        $stmt = $conn->prepare("DELETE FROM marks WHERE id = ?");
        $stmt->bind_param("i", $mark_id);
        $stmt->execute() ? $message = "Mark deleted!" : $message = "Delete failed.";
        $stmt->close();
    }
}

if (isset($_POST['add_marks'])) {
    $student_id = intval($_POST['student_id']);
    $subject = trim($_POST['subject']);
    $exam_name = trim($_POST['exam_name']);
    $marks_obtained = floatval($_POST['marks_obtained']);
    $total_marks = floatval($_POST['total_marks']);
    $date = $_POST['date'] ?: date('Y-m-d');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($student_id > 0 && !empty($subject) && !empty($exam_name) && $total_marks > 0) {
        $stmt = $conn->prepare("INSERT INTO marks (student_id, subject, exam_name, marks_obtained, total_marks, date, teacher_username, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $teacher_username = $_SESSION['username'];
        $stmt->bind_param("issddsss", $student_id, $subject, $exam_name, $marks_obtained, $total_marks, $date, $teacher_username, $remarks);
        
        if ($stmt->execute()) {
            header("Location: marks.php?class=" . urlencode($class_sel) . "&student_search=" . urlencode($search_term) . "&msg=added");
            exit;
        } else {
            $message = "Failed to add marks.";
        }
        $stmt->close();
    } else {
        $message = "Fill all required fields.";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'added') {
    $message = "Marks added!";
}

$students = [];
$subjects = [];

if (!empty($class_sel)) {
    $sql = "SELECT id, fullname, username FROM users WHERE class = ? AND role = 'student'";
    $params = [$class_sel];
    $types = "s";

    if ($search_term) {
        $sql .= " AND fullname LIKE ?";
        $params[] = "%{$search_term}%";
        $types .= "s";
    }

    $sql .= " ORDER BY fullname";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE class = ? ORDER BY subject_name");
    $stmt->bind_param("s", $class_sel);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $subjects[] = $row['subject_name'];
    $stmt->close();
}

$marks_data = [];
$promotion_eligibility = [];

if (!empty($students)) {
    $ids = array_column($students, 'id');
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("SELECT * FROM marks WHERE student_id IN ($placeholders) ORDER BY date DESC, subject");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $marks_data[$row['student_id']][] = $row;
        }
        $stmt->close();
        
        // Calculate promotion eligibility for each student
        foreach ($students as $stu) {
            $student_id = $stu['id'];
            $final_marks = array_filter($marks_data[$student_id] ?? [], function($m) {
                return strtolower($m['exam_name']) === 'final exam';
            });
            
            $total_obtained = 0;
            $total_total = 0;
            foreach ($final_marks as $mark) {
                $total_obtained += floatval($mark['marks_obtained']);
                $total_total += floatval($mark['total_marks']);
            }
            
            $agg_perc = 0;
            if ($total_total > 0) {
                $agg_perc = ($total_obtained / $total_total) * 100;
            }
            
            $promotion_eligibility[$student_id] = [
                'percentage' => $agg_perc,
                'eligible' => ($agg_perc >= 40),
                'has_final_marks' => !empty($final_marks)
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marks - DAR-UL-HUDA PUBLIC SCHOOL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" href="../images/logo.png">
    <style>
        :root {
            --blue: #4367f7;
            --light-bg: #f8fbff;
            --card-shadow: 0 6px 20px rgba(0,0,0,0.08);
            --radius: 20px;
        }
        * { box-sizing: border-box; }
        body { background: var(--light-bg); font-family: 'Roboto', sans-serif; margin: 0; overflow-x: hidden; }

        /* Sidebar - Always visible on desktop */
        .erp-sidebar {
            background: white;
            width: 280px;
            padding: 30px 24px;
            box-shadow: var(--card-shadow);
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 1001;
            overflow-y: auto;
        }

        .erp-logo { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 1.5rem; margin-bottom: 30px; display: flex; align-items: center; gap: 14px; color: var(--blue); }
        .erp-logo img { height: 44px; border-radius: 12px; }
        .erp-user-info { margin-bottom: 30px; }
        .erp-user-name { font-weight: 600; font-size: 1.1rem; color: #333; }
        .erp-user-meta { color: #666; font-size: 0.95rem; margin-top: 4px; }
        .erp-menu { list-style: none; padding: 0; margin: 0 0 30px; }
        .erp-menu a {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 18px;
            border-radius: 14px;
            color: #444;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        .erp-menu a:hover, .erp-menu .active a { background: #e8f0ff; color: var(--blue); }

        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            background: white;
            padding: 14px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1002;
            align-items: center;
            justify-content: space-between;
        }
        .hamburger {
            font-size: 1.9rem;
            color: var(--blue);
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
        }

        /* Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .sidebar-overlay.active { display: block; }

        /* Main Content */
        .erp-content {
            margin-left: 280px;
            padding: 40px 6vw;
            min-height: 100vh;
        }
        .page-title { font-family: 'Montserrat', sans-serif; font-size: 2.2rem; color: var(--blue); font-weight: 800; margin-bottom: 10px; }
        .subtitle { font-size: 1.2rem; color: #555; margin-bottom: 30px; }

        .card-custom {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        .card-custom .card-body { padding: 30px; }

        .student-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .student-header {
            background: var(--blue);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .student-header h5 { margin: 0; font-size: 1.3rem; }
        .view-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.95rem;
        }
        
        .promote-btn {
            background: #28a745;
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.95rem;
            text-decoration: none;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .promote-btn:hover {
            background: #218838;
            color: white;
        }
        
        .promote-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .eligibility-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .eligibility-eligible {
            background: #d4edda;
            color: #155724;
        }
        
        .eligibility-not-eligible {
            background: #f8d7da;
            color: #721c24;
        }
        
        .eligibility-no-marks {
            background: #fff3cd;
            color: #856404;
        }

        .form-section { padding: 30px; background: #f9fbff; }
        .table-section { padding: 30px; }

        .marks-table thead { background: #eef2ff; }

        /* Quick Remarks Styles */
        .quick-remarks {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        
        .remark-btn {
            padding: 4px 12px;
            font-size: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            background: white;
            color: #495057;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .remark-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Color coding for quick remarks */
        .remark-excellent { border-color: #198754; color: #198754; }
        .remark-excellent:hover { background: #198754; color: white; }
        
        .remark-very-good { border-color: #20c997; color: #20c997; }
        .remark-very-good:hover { background: #20c997; color: white; }
        
        .remark-good { border-color: #0dcaf0; color: #0dcaf0; }
        .remark-good:hover { background: #0dcaf0; color: white; }
        
        .remark-satisfactory { border-color: #ffc107; color: #ffc107; }
        .remark-satisfactory:hover { background: #ffc107; color: black; }
        
        .remark-needs-improvement { border-color: #fd7e14; color: #fd7e14; }
        .remark-needs-improvement:hover { background: #fd7e14; color: white; }
        
        .remark-poor { border-color: #dc3545; color: #dc3545; }
        .remark-poor:hover { background: #dc3545; color: white; }
        
        /* Remarks indicator in table */
        .remarks-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .remarks-excellent-indicator { background: #198754; }
        .remarks-very-good-indicator { background: #20c997; }
        .remarks-good-indicator { background: #0dcaf0; }
        .remarks-satisfactory-indicator { background: #ffc107; }
        .remarks-needs-improvement-indicator { background: #fd7e14; }
        .remarks-poor-indicator { background: #dc3545; }

        @media (max-width: 991.98px) {
            .mobile-header { display: flex; }
            .erp-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .erp-sidebar.active { transform: translateX(0); }
            .erp-content {
                margin-left: 0;
                padding-top: 90px;
                padding: 30px 5vw;
            }
            .student-header { padding: 20px; flex-direction: column; align-items: flex-start; gap: 15px; }
            .view-btn { align-self: flex-end; }
            .form-section, .table-section { padding: 20px; }
            .quick-remarks {
                overflow-x: auto;
                padding-bottom: 5px;
                flex-wrap: nowrap;
            }
            .remark-btn {
                flex-shrink: 0;
            }
        }
        @media (max-width: 576px) {
            .page-title { font-size: 1.9rem; }
            .subtitle { font-size: 1.1rem; }
            .card-custom .card-body, .form-section, .table-section { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- Mobile Header -->
<header class="mobile-header">
    <div class="hamburger" id="hamburgerBtn">☰</div>
    <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 1.4rem; color: var(--blue);">
        TEACHER
    </div>
    <div></div>
</header>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="erp-sidebar" id="sidebar">
    <div class="erp-logo">
        <img src="../images/logo.png" alt="Logo">
        <span>TEACHER</span>
    </div>
    <div class="erp-user-info">
        <div class="erp-user-name"><?= htmlspecialchars($teacherName) ?></div>
        <div class="erp-user-meta">Class: <?= htmlspecialchars($teacherClass ?: 'All') ?></div>
        <div class="erp-user-meta"><?= htmlspecialchars($teacherEmail) ?></div>
    </div>
    <ul class="erp-menu">
        <li><a href="dashboard.php"><span>🏠</span> Dashboard</a></li>
        <li><a href="students.php"><span>👨‍🎓</span> Students</a></li>
        <li><a href="subjects.php"><span>📚</span> Subjects</a></li>
        <li><a href="attendance.php"><span>🗓️</span> Attendance</a></li>
        <li class="active"><a href="marks.php"><span>📊</span> Marks</a></li>
        <li><a href="notices.php"><span>📢</span> Notices</a></li>
        <li><a href="salary.php"><span>💰</span> My Salary</a></li>
    </ul>
    <a href="../logout.php" class="btn btn-danger w-100">Logout</a>
</aside>

<!-- Main Content -->
<main class="erp-content">
    <div class="page-title">Marks Management</div>
    <div class="subtitle">Class: <?= htmlspecialchars($class_sel ?: '—') ?></div>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, 'deleted') || strpos($message, 'added') || strpos($message, 'promoted') ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card-custom">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end mb-4">
                <?php if (count($allowed_classes) > 1): ?>
                    <div class="col-md-3 col-12">
                        <select name="class" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($allowed_classes as $c): ?>
                                <option value="<?= $c ?>" <?= $c === $class_sel ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="class" value="<?= htmlspecialchars($class_sel) ?>">
                <?php endif; ?>
                <div class="col-md-6 col-12">
                    <input type="text" name="student_search" class="form-control" placeholder="Search student by name..." value="<?= htmlspecialchars($search_term) ?>">
                </div>
                <div class="col-md-3 col-12">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($allowed_classes)): ?>
        <div class="alert alert-danger text-center py-5">No class assigned to you.</div>
    <?php elseif (empty($students)): ?>
        <div class="alert alert-info text-center py-5">
            No students found in Class <?= htmlspecialchars($class_sel) ?>
            <?= $search_term ? " matching your search." : "." ?>
        </div>
    <?php else: ?>
        <?php foreach ($students as $stu): 
            $student_id = $stu['id'];
            $eligibility = $promotion_eligibility[$student_id] ?? ['percentage' => 0, 'eligible' => false, 'has_final_marks' => false];
            $is_last_class = ($class_sel === '8');
        ?>
            <div class="student-card">
                <div class="student-header">
                    <div>
                        <h5><?= htmlspecialchars($stu['fullname']) ?> <small>(<?= htmlspecialchars($stu['username']) ?>)</small></h5>
                        <?php if (!$is_last_class): ?>
                            <?php if ($eligibility['has_final_marks']): ?>
                                <?php if ($eligibility['eligible']): ?>
                                    <span class="eligibility-badge eligibility-eligible">
                                        <i class="bi bi-check-circle"></i> Eligible for Promotion (<?= number_format($eligibility['percentage'], 2) ?>%)
                                    </span>
                                <?php else: ?>
                                    <span class="eligibility-badge eligibility-not-eligible">
                                        <i class="bi bi-x-circle"></i> Not Eligible (<?= number_format($eligibility['percentage'], 2) ?>%)
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="eligibility-badge eligibility-no-marks">
                                    <i class="bi bi-exclamation-circle"></i> No Final Exam Marks
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="eligibility-badge" style="background:#cce5ff;color:#004085;">
                                <i class="bi bi-award"></i> Final Class (8th)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button class="view-btn" onclick="openMarksheet(<?= $student_id ?>)">
                            View Marksheet
                        </button>
                        <?php if (!$is_last_class && $eligibility['eligible']): ?>
                            <a href="?class=<?= urlencode($class_sel) ?>&student_search=<?= urlencode($search_term) ?>&promote=<?= $student_id ?>" 
                               class="promote-btn" 
                               onclick="return confirmPromotion(<?= $student_id ?>, '<?= htmlspecialchars($stu['fullname']) ?>', <?= number_format($eligibility['percentage'], 2) ?>)">
                                <i class="bi bi-arrow-up-circle"></i> Promote
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-section">
                    <form method="post" class="row g-3 add-form">
                        <input type="hidden" name="student_id" value="<?= $student_id ?>">
                        <div class="col-md-3 col-12">
                            <select name="subject" class="form-select" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option><?= htmlspecialchars($sub) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-12">
                            <select name="exam_name_select" id="exam_select_<?= $student_id ?>" class="form-select" onchange="toggleExam(<?= $student_id ?>)">
                                <option value="">Select Exam</option>
                                <option>Final Exam</option>
                                <option>Mid Term</option>
                                <option>December Test</option>
                                <option>Monthly Test</option>
                                <option>Quiz</option>
                                <option>Other</option>
                            </select>
                            <input type="text" name="exam_name" id="exam_custom_<?= $student_id ?>" class="form-control mt-2" placeholder="Custom exam name" style="display:none;">
                        </div>
                        <div class="col-6 col-md-1">
                            <input type="number" name="marks_obtained" class="form-control" placeholder="Obt." step="0.1" min="0" required>
                        </div>
                        <div class="col-6 col-md-1">
                            <input type="number" name="total_marks" class="form-control" placeholder="Total" step="0.1" min="1" required>
                        </div>
                        <div class="col-md-3 col-12">
                            <input type="text" name="remarks" id="remarks_<?= $student_id ?>" class="form-control" placeholder="Remarks (optional)">
                            <div class="quick-remarks">
                                <button type="button" class="remark-btn remark-excellent" onclick="setRemarks(<?= $student_id ?>, 'Excellent')">Excellent</button>
                                <button type="button" class="remark-btn remark-very-good" onclick="setRemarks(<?= $student_id ?>, 'Very Good')">Very Good</button>
                                <button type="button" class="remark-btn remark-good" onclick="setRemarks(<?= $student_id ?>, 'Good')">Good</button>
                                <button type="button" class="remark-btn remark-satisfactory" onclick="setRemarks(<?= $student_id ?>, 'Satisfactory')">Satisfactory</button>
                                <button type="button" class="remark-btn remark-needs-improvement" onclick="setRemarks(<?= $student_id ?>, 'Needs Improvement')">Needs Impr.</button>
                                <button type="button" class="remark-btn remark-poor" onclick="setRemarks(<?= $student_id ?>, 'Poor')">Poor</button>
                            </div>
                        </div>
                        <div class="col-md-1 col-6">
                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-1 col-6">
                            <button type="submit" name="add_marks" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>

                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover marks-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Obtained</th>
                                    <th>Total</th>
                                    <th>%</th>
                                    <th>Date</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $idx = 1;
                                $list = $marks_data[$student_id] ?? [];
                                if (!empty($list)):
                                    foreach ($list as $m):
                                        $pct = $m['total_marks'] > 0 ? round(($m['marks_obtained'] / $m['total_marks']) * 100, 1) : 0;
                                        $remarks = $m['remarks'] ?: '-';
                                        
                                        // Determine remark color class
                                        $remark_class = '';
                                        if (stripos($remarks, 'excellent') !== false) $remark_class = 'remarks-excellent-indicator';
                                        elseif (stripos($remarks, 'very good') !== false) $remark_class = 'remarks-very-good-indicator';
                                        elseif (stripos($remarks, 'good') !== false) $remark_class = 'remarks-good-indicator';
                                        elseif (stripos($remarks, 'satisfactory') !== false) $remark_class = 'remarks-satisfactory-indicator';
                                        elseif (stripos($remarks, 'needs improvement') !== false) $remark_class = 'remarks-needs-improvement-indicator';
                                        elseif (stripos($remarks, 'poor') !== false) $remark_class = 'remarks-poor-indicator';
                                ?>
                                <tr>
                                    <td><?= $idx++ ?></td>
                                    <td><?= htmlspecialchars($m['subject']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($m['exam_name']) ?>
                                        <?php if (strtolower($m['exam_name']) === 'final exam'): ?>
                                            <span class="badge bg-warning text-dark">Final</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($m['marks_obtained'], 1) ?></td>
                                    <td><?= number_format($m['total_marks'], 1) ?></td>
                                    <td class="fw-bold <?= $pct >= 40 ? 'text-success' : 'text-danger' ?>"><?= $pct ?>%</td>
                                    <td><?= date('d/m/Y', strtotime($m['date'])) ?></td>
                                    <td>
                                        <?php if ($remarks !== '-' && $remark_class): ?>
                                            <span class="remarks-indicator <?= $remark_class ?>"></span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($remarks) ?>
                                    </td>
                                    <td>
                                        <a href="?class=<?= urlencode($class_sel) ?>&student_search=<?= urlencode($search_term) ?>&delete_mark=<?= $m['id'] ?>"
                                           class="btn btn-sm btn-danger" onclick="return confirm('Delete this mark?')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">No marks recorded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<!-- Marksheet Modal -->
<div class="modal fade" id="marksheetModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Marksheet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="marksheetContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mobile menu
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

function toggleMenu() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', toggleMenu);
    hamburgerBtn.addEventListener('touchstart', e => { e.preventDefault(); toggleMenu(); });
}
overlay.addEventListener('click', toggleMenu);
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && sidebar.classList.contains('active')) toggleMenu();
});

// Custom exam name
function toggleExam(id) {
    const sel = document.getElementById('exam_select_' + id);
    const cus = document.getElementById('exam_custom_' + id);
    cus.style.display = sel.value === 'Other' ? 'block' : 'none';
}

// Quick remarks selection
function setRemarks(studentId, remark) {
    const remarksInput = document.getElementById('remarks_' + studentId);
    remarksInput.value = remark;
    remarksInput.focus();
}

// Set exam_name on submit
document.querySelectorAll('.add-form').forEach(form => {
    form.addEventListener('submit', function() {
        const id = this.querySelector('[name="student_id"]').value;
        const sel = document.getElementById('exam_select_' + id);
        const cus = document.getElementById('exam_custom_' + id);
        const target = this.querySelector('[name="exam_name"]');
        target.value = sel.value === 'Other' ? cus.value.trim() : sel.value;
    });
});

// Promotion confirmation
function confirmPromotion(studentId, studentName, percentage) {
    return confirm(`Promote ${studentName}?\n\nFinal Exam Aggregate: ${percentage}%\n\nThis student will be moved to the next class.`);
}

// Marksheet modal
function openMarksheet(id) {
    const modal = new bootstrap.Modal('#marksheetModal');
    const content = document.getElementById('marksheetContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div> Loading...</div>';
    modal.show();

    fetch('marksheet.php?student_id=' + id)
        .then(r => r.ok ? r.text() : Promise.reject('Failed'))
        .then(html => content.innerHTML = html)
        .catch(() => content.innerHTML = '<p class="text-danger text-center">Failed to load marksheet.</p>');
}

// Auto fade alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Real-time percentage calculation
document.addEventListener('DOMContentLoaded', function() {
    // Add percentage calculation to form
    document.querySelectorAll('.add-form').forEach(form => {
        const marksInput = form.querySelector('input[name="marks_obtained"]');
        const totalInput = form.querySelector('input[name="total_marks"]');
        
        function calculatePercentage() {
            const marks = parseFloat(marksInput.value) || 0;
            const total = parseFloat(totalInput.value) || 0;
            
            if (total > 0) {
                const percentage = (marks / total) * 100;
                // You can optionally display this somewhere
                console.log('Calculated percentage:', percentage.toFixed(2) + '%');
            }
        }
        
        marksInput.addEventListener('input', calculatePercentage);
        totalInput.addEventListener('input', calculatePercentage);
    });
});
</script>
</body>
</html>