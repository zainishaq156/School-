<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$classes = ['PG','Nursery','Prep','1','2','3','4','5','6','7','8'];
$message = '';
$error = '';
$class_filter = $_GET['class'] ?? $classes[0];

// Handle Add
if (isset($_POST['add_subject'])) {
    $class = trim($_POST['class'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $teacher_username = $_SESSION['username'];
    if ($class && $subject_name) {
        $stmt = $conn->prepare("INSERT INTO subjects (class, subject_name, teacher_username) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $class, $subject_name, $teacher_username);
        if ($stmt->execute()) {
            $message = "Subject added successfully!";
            $class_filter = $class;
        } else {
            $error = "Error adding subject.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// Handle Edit
if (isset($_POST['edit_subject'])) {
    $edit_id = (int)$_POST['edit_id'];
    $class = trim($_POST['class'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    if ($class && $subject_name) {
        $stmt = $conn->prepare("UPDATE subjects SET class=?, subject_name=? WHERE id=?");
        $stmt->bind_param("ssi", $class, $subject_name, $edit_id);
        if ($stmt->execute()) {
            $message = "Subject updated!";
            $class_filter = $class;
        } else {
            $error = "Error updating subject.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM subjects WHERE id=$id");
    $message = "Subject deleted.";
}

// Handle Edit GET
$editing = false;
$edit_subject = [];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM subjects WHERE id=$edit_id LIMIT 1");
    if ($res && $res->num_rows === 1) {
        $editing = true;
        $edit_subject = $res->fetch_assoc();
        $class_filter = $edit_subject['class'];
    }
}

// Subject query for filter
if (isset($_GET['show_all'])) {
    $subjects = $conn->query("SELECT * FROM subjects ORDER BY class, subject_name")->fetch_all(MYSQLI_ASSOC);
    $class_filter = '';
} else {
    $subjects = $conn->query("SELECT * FROM subjects WHERE class='$class_filter' ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
}

// Get real subject count for selected class or all
if ($class_filter && !$editing && !isset($_GET['show_all'])) {
    $countRes = $conn->query("SELECT COUNT(*) as cnt FROM subjects WHERE class='$class_filter'");
} else {
    $countRes = $conn->query("SELECT COUNT(*) as cnt FROM subjects");
}
$realSubjectCount = $countRes ? $countRes->fetch_assoc()['cnt'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subjects - Teacher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 + Bootstrap Icons + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f6fbff; font-family: 'Roboto', Arial, sans-serif;}
        .subjects-content {
            max-width: 1020px; margin: 28px auto 26px auto;
            background: #fff; border-radius: 17px;
            box-shadow: 0 2px 18px #adcfff28;
            padding: 36px 16px 30px 16px;
        }
        h2, .header-main { color:#204982;font-family:Montserrat,sans-serif; font-weight:800;}
        .table thead { background: linear-gradient(90deg,#e6f0fd 65%,#d8f5ff 100%); color: #2446a6; font-size:1.03em;}
        .badge-total {
            background: #2446a6; color: #fff; font-size: .99em;
            border-radius: 30px; padding: 5px 16px; margin-left: 9px;
        }
        .success, .error {
            padding: 11px 18px; border-radius: 9px; margin-bottom: 16px; font-weight: 600;
        }
        .success { background: #d1f9e8; color: #168c34;}
        .error { background: #ffd3d3; color: #c60000;}
        .edit-btn, .delete-btn {
            border-radius: 7px; font-weight: 600; border:none; cursor:pointer; margin:0 3px;
            font-size: 1em; min-width: 70px;
        }
        .edit-btn { background: #40c950; color: #fff;}
        .edit-btn:hover { background: #289a37;}
        .delete-btn { background: #ea4444; color: #fff;}
        .delete-btn:hover { background: #b82c2c;}
        .back-link { color:#2446a6; text-decoration:underline; font-size:1.07em; margin-top:25px; display:inline-block;}
        @media (max-width: 700px) {
            .subjects-content { padding:12px 2vw;}
            .table th, .table td { font-size:0.97em;}
        }
        @media (max-width:500px) {
            .subjects-content { padding:7px 0.5vw;}
        }
    </style>
</head>
<body>
<div class="subjects-content shadow">
    <h2 class="header-main mb-4 d-flex align-items-center">
        <span class="me-2 fs-3">📚</span> Subjects Management
    </h2>
    <?php if ($message): ?><div class="success" id="msgbox"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error" id="msgbox"><?= $error ?></div><?php endif; ?>

    <!-- Filter Row -->
    <form method="get" class="row g-3 align-items-end mb-4">
        <div class="col-12 col-md-4">
            <label for="filter-class" class="form-label mb-1">Class</label>
            <select name="class" id="filter-class" class="form-select">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c ?>" <?= $c == $class_filter ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Find</button>
        </div>
        <div class="col-auto">
            <a href="?show_all=1" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i> Show All</a>
        </div>
    </form>

    <!-- Add/Edit form -->
    <div class="card mb-4 shadow-sm">
    <div class="card-body">
        <?php if ($editing): ?>
        <form method="post" class="row g-3 align-items-end">
            <input type="hidden" name="edit_id" value="<?= $edit_subject['id'] ?>">
            <div class="col-12 col-md-4">
                <label class="form-label">Class *</label>
                <select name="class" class="form-select" required>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c ?>" <?= $c == $edit_subject['class'] ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Subject *</label>
                <input type="text" name="subject_name" class="form-control" required value="<?= htmlspecialchars($edit_subject['subject_name']) ?>">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-success w-100" type="submit" name="edit_subject"><i class="bi bi-pencil-square"></i> Update</button>
                <a href="subjects.php?class=<?= urlencode($edit_subject['class']) ?>" class="btn btn-warning w-100">Cancel</a>
            </div>
        </form>
        <?php else: ?>
        <form method="post" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Class *</label>
                <select name="class" class="form-select" required>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c ?>" <?= $c == $class_filter ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Subject *</label>
                <input type="text" name="subject_name" class="form-control" required>
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-primary w-100" type="submit" name="add_subject"><i class="bi bi-plus-circle"></i> Add</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    </div>

    <div class="mb-2 mt-2 fs-5 fw-semibold text-primary">
        <?= $class_filter ? "Class $class_filter Subjects" : "All Subjects" ?>
        <span class="badge badge-total"><?= $realSubjectCount ?> total</span>
    </div>

    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead>
        <tr>
            <th style="width:50px;">#</th>
            <th>Class</th> <!-- Class column added -->
            <th>Subject</th>
            <th>Added By</th>
            <th style="width:170px;">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php $i=1; foreach($subjects as $s): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($s['class']) ?></td> <!-- Show class here -->
            <td><?= htmlspecialchars($s['subject_name']) ?></td>
            <td><?= htmlspecialchars($s['teacher_username']) ?></td>
            <td>
                <a href="subjects.php?edit=<?= $s['id'] ?>" class="edit-btn btn btn-success btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                <a href="subjects.php?delete=<?= $s['id'] ?>&class=<?= urlencode($s['class']) ?>" class="delete-btn btn btn-danger btn-sm" onclick="return confirm('Delete this subject?')"><i class="bi bi-trash"></i> Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($subjects)): ?>
        <tr><td colspan="5" class="text-center text-muted">No subjects for this class.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    <a href="dashboard.php" class="back-link"><i class="bi bi-arrow-left-circle"></i> Back to Dashboard</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide alert messages
setTimeout(function(){
    var m=document.getElementById('msgbox');
    if(m) m.style.display='none';
}, 2200);
</script>
</body>
</html>
