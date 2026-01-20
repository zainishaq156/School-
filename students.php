<?php
session_start();
require '../db.php';

// Only allow teachers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Variables for form pre-fill (edit mode)
$edit_mode = false;
$edit_id = 0;
$form = [
    'fullname' => '',
    'father_name' => '',
    'username' => '',
    'email' => '',
    'class' => '',
    'dob' => '',
    'contact' => '',
    'address' => ''
];

// ================= EDIT STUDENT (load data) =================
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $edit_mode = true;
        $form = [
            'fullname' => $row['fullname'],
            'father_name' => $row['father_name'],
            'username' => $row['username'],
            'email' => $row['email'],
            'class' => $row['class'],
            'dob' => $row['dob'],
            'contact' => $row['contact'],
            'address' => $row['address']
        ];
    } else {
        $error = "Student not found.";
    }
    $stmt->close();
}

// ================= UPDATE STUDENT =================
if (isset($_POST['update_student'])) {
    $edit_id = (int)$_POST['student_id'];
    $fullname     = trim($_POST['fullname'] ?? '');
    $father_name  = trim($_POST['father_name'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $class        = trim($_POST['class'] ?? '');
    $dob          = $_POST['dob'] ?? '';
    $contact      = trim($_POST['contact'] ?? '');
    $address      = trim($_POST['address'] ?? '');

    if ($fullname && $father_name && $username && $class) {
        // Check if username already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $edit_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken by another user!";
        } else {
            $stmt = $conn->prepare("
                UPDATE users SET 
                fullname=?, father_name=?, username=?, email=?, class=?, dob=?, contact=?, address=?
                WHERE id=? AND role='student'
            ");
            $stmt->bind_param(
                "ssssssssi",
                $fullname, $father_name, $username, $email, $class, $dob, $contact, $address, $edit_id
            );

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = "Student updated successfully!";
                // Refresh page without ?edit parameter
                header("Location: students.php");
                exit;
            } else {
                $error = "No changes made or error updating student.";
            }
        }
        $stmt->close();
    } else {
        $error = "Required fields are missing.";
    }
}

// ================= ADD STUDENT =================
if (isset($_POST['add_student']) && !$edit_mode) {
    $fullname     = trim($_POST['fullname'] ?? '');
    $father_name  = trim($_POST['father_name'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $class        = trim($_POST['class'] ?? '');
    $dob          = $_POST['dob'] ?? '';
    $contact      = trim($_POST['contact'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $password     = $_POST['password'] ?? '';

    if ($fullname && $father_name && $username && $password && $class) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users 
                (fullname, father_name, username, email, class, dob, contact, address, password, role)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')
            ");

            $stmt->bind_param(
                "sssssssss",
                $fullname, $father_name, $username, $email, $class, $dob, $contact, $address, $hash
            );

            if ($stmt->execute()) {
                $message = "Student added successfully!";
            } else {
                $error = "Error adding student.";
            }
        }
        $stmt->close();
    } else {
        $error = "All fields marked * are required.";
    }
}

// ================= FETCH TEACHER =================
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'teacher' LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacherName = $teacher['fullname'] ?? $teacher['username'];
$teacherClass = $teacher['class'] ?? '';
$teacherEmail = $teacher['email'] ?? '';

// ================= FETCH STUDENTS =================
if ($teacherClass) {
    $stmt = $conn->prepare("
        SELECT id, fullname, father_name, username, email, class, dob, contact, address 
        FROM users 
        WHERE role='student' AND class=?
        ORDER BY fullname
    ");
    $stmt->bind_param("s", $teacherClass);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT id, fullname, father_name, username, email, class, dob, contact, address 
        FROM users 
        WHERE role='student'
        ORDER BY fullname
    ");
}

$students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Students - DAR-UL-HUDA PUBLIC SCHOOL</title>
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
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .hamburger:active { background: rgba(67,103,247,0.15); }

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
        .welcome-text { font-size: 1.2rem; color: #555; margin-bottom: 30px; }

        .card-custom {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        .card-custom .card-body { padding: 30px; }

        .table th, .table td { vertical-align: middle; }

        /* Responsive */
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
        }
        @media (max-width: 576px) {
            .page-title { font-size: 1.9rem; }
            .welcome-text { font-size: 1.1rem; }
            .card-custom .card-body { padding: 20px; }
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
        <li class="active"><a href="students.php"><span>👨‍🎓</span> Students</a></li>
        <li><a href="subjects.php"><span>📚</span> Subjects</a></li>
        <li><a href="attendance.php"><span>🗓️</span> Attendance</a></li>
        <li><a href="marks.php"><span>📊</span> Marks</a></li>
        <li><a href="notices.php"><span>📢</span> Notices</a></li>
        <li><a href="salary.php"><span>💰</span> My Salary</a></li>
    </ul>
    <a href="../logout.php" class="btn btn-danger w-100">Logout</a>
</aside>

<!-- Main Content -->
<main class="erp-content">
    <div class="page-title">Manage Students</div>
    <div class="welcome-text">Class: <?= htmlspecialchars($teacherClass ?: 'All Classes') ?></div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add / Edit Form -->
    <div class="card-custom">
        <div class="card-body">
            <h5 class="mb-4"><?= $edit_mode ? 'Edit Student' : 'Add New Student' ?></h5>
            <form method="post" class="row g-3">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="student_id" value="<?= $edit_id ?>">
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($form['fullname']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Father Name <span class="text-danger">*</span></label>
                    <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($form['father_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Registration No. <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($form['username']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($form['email']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <input type="text" name="class" class="form-control" value="<?= htmlspecialchars($edit_mode ? $form['class'] : $teacherClass) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($form['dob']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact</label>
                    <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($form['contact']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($form['address']) ?>">
                </div>

                <?php if (!$edit_mode): ?>
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <?php endif; ?>

                <div class="col-12">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_student" class="btn btn-success me-2">
                            Update Student
                        </button>
                        <a href="students.php" class="btn btn-secondary">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_student" class="btn btn-primary">
                            Add Student
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card-custom">
        <div class="card-body">
            <h5 class="mb-4">All Students</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Father Name</th>
                            <th>Registration No.</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>DOB</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No students found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($students as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['id']) ?></td>
                                <td><?= htmlspecialchars($s['fullname']) ?></td>
                                <td><?= htmlspecialchars($s['father_name']) ?></td>
                                <td><?= htmlspecialchars($s['username']) ?></td>
                                <td><?= htmlspecialchars($s['email']) ?></td>
                                <td><?= htmlspecialchars($s['class']) ?></td>
                                <td><?= htmlspecialchars($s['dob']) ?></td>
                                <td><?= htmlspecialchars($s['contact']) ?></td>
                                <td><?= htmlspecialchars($s['address']) ?></td>
                                <td>
                                    <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-warning text-white">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mobile Menu Toggle
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
    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
        toggleMenu();
    }
});

// Auto fade success message
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('alert-success')) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    });
}, 5000);
</script>
</body>
</html>