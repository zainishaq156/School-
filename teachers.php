<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$classes = ['PG','Nursery','Prep','1','2','3','4','5','6','7','8'];
$message = '';
$error = '';

// --- Add Teacher ---
if (isset($_POST['add_teacher'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname && $username && $password && $class) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users (fullname, username, email, password, class, contact, address, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'teacher')"
            );
            $stmt->bind_param("sssssss", $fullname, $username, $email, $hash, $class, $contact, $address);
            if ($stmt->execute()) {
                $message = "Teacher added successfully!";
            } else {
                $error = "Error adding teacher.";
            }
        }
    } else {
        $error = "All fields marked * are required.";
    }
}

// --- Edit Teacher (form show) ---
$editing = false;
$edit_teacher = [];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $result = $conn->query(
        "SELECT id, fullname, username, email, class, contact, address FROM users WHERE id=$edit_id AND role='teacher' LIMIT 1"
    );
    if ($result && $result->num_rows === 1) {
        $editing = true;
        $edit_teacher = $result->fetch_assoc();
    }
}

// --- Update Teacher ---
if (isset($_POST['edit_teacher'])) {
    $edit_id = (int)$_POST['teacher_id'];
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname && $class) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "UPDATE users SET fullname=?, email=?, class=?, contact=?, address=?, password=? WHERE id=? AND role='teacher'"
            );
            $stmt->bind_param("ssssssi", $fullname, $email, $class, $contact, $address, $hash, $edit_id);
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET fullname=?, email=?, class=?, contact=?, address=? WHERE id=? AND role='teacher'"
            );
            $stmt->bind_param("sssssi", $fullname, $email, $class, $contact, $address, $edit_id);
        }
        if ($stmt->execute()) {
            $message = "Teacher info updated!";
            $editing = false;
        } else {
            $error = "Error updating teacher.";
        }
    } else {
        $error = "Full Name and Class are required.";
    }
}

// --- Delete Teacher ---
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$delete_id AND role='teacher'");
    $message = "Teacher deleted.";
}

// --- Fetch all teachers ---
$result = $conn->query(
    "SELECT id, fullname, username, email, class, contact, address FROM users WHERE role = 'teacher' ORDER BY id DESC"
);
$teachers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Teachers - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg,#e9f2ff 0,#f9fafe 100%); min-height: 100vh; font-family: 'Roboto', Arial, sans-serif;}
        .side-nav { background: #1849a6; color: #fff; min-height: 100vh; min-width: 220px; max-width: 235px; position: fixed; left:0; top:0; z-index:1020; }
        .side-nav .logo { font-size:1.6em; font-weight:800; padding: 24px 14px 20px 24px; letter-spacing:2px; border-bottom: 1px solid #2a4eaf;}
        .side-nav ul { list-style:none; padding:0; margin: 30px 0 0 0;}
        .side-nav ul li { margin:10px 0;}
        .side-nav ul li a { color: #fff; text-decoration: none; display: flex; align-items:center; gap:10px; font-size:1.09em; padding: 10px 22px; border-radius: 8px; }
        .side-nav ul li a.active, .side-nav ul li a:hover { background: #102c5d; color: #ffe599;}
        .side-nav .logout-btn { position: absolute; bottom: 25px; left: 18px; width: 90%; }
        @media (max-width:900px) {
            .side-nav { min-width: 70vw; max-width: 100vw;}
        }
        @media (max-width:700px) {
            .side-nav { position:relative; min-width:100vw; border-radius:0; box-shadow:none; }
        }
        .main-content { margin-left: 235px; padding: 34px 2vw 2vw 2vw; background:#f9fafe; min-height: 100vh;}
        @media (max-width:900px) { .main-content { margin-left: 0;} }
        .header-bar {
            display: flex; justify-content: space-between; align-items: center;
            background: #fff; border-radius: 13px; box-shadow:0 1px 8px #1749a611;
            padding: 18px 22px; margin-bottom: 30px;
        }
        .header-bar .school-title { font-family: 'Montserrat', Arial, sans-serif; font-weight:800; color:#1849a6; font-size:1.2em;}
        .header-bar .admin-logout { color: #d7263d; font-weight:600; font-size: 1.04em; text-decoration:none;}
        .header-bar .admin-logout:hover { color:#fff; background:#d7263d; border-radius:7px; padding: 6px 13px;}
        .card-section { max-width: 1040px; margin:0 auto 20px auto;}
        .card-section h3 { color:#2446a6; font-family: 'Montserrat', Arial, sans-serif; font-weight:700; font-size:1.35em;}
        .success, .error { max-width:500px; margin: 0 auto 18px auto; padding:11px 18px; border-radius: 10px; font-size:1.09em; box-shadow:0 1px 8px #bddfff22;}
        .success { color: #168c34; background: #d1f9e8;}
        .error { color: #c60000; background: #ffd3d3;}
        .card-box { border-radius: 18px; background: linear-gradient(120deg,#f5fbff 0,#d1eaff 100%); box-shadow:0 2px 13px #c8d3ea1a; padding: 24px 22px 18px 22px; margin-bottom: 22px;}
        .form-label { font-weight: 600; color: #2446a6; }
        .submit-btn { background: linear-gradient(90deg,#308cf7 60%,#21e2af 100%); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: 10px 32px; transition:background .15s;}
        .submit-btn:hover { background: linear-gradient(90deg,#2446a6 40%,#1cd5b1 100%);}
        .edit-btn, .del-btn, .cancel-btn {
            border: none; border-radius: 7px; margin: 0 3px;
            font-weight: 600; padding: 7px 17px; display: inline-flex; align-items: center; gap: 6px; transition: .13s;
        }
        .edit-btn { background: #ffd350; color: #322600; }
        .edit-btn:hover { background: #fd9f1b; color: #fff; }
        .del-btn { background: #ea4444; color: #fff; }
        .del-btn:hover { background: #ba2828; color: #fff; }
        .cancel-btn { background: #e2e8f0; color: #2446a6; }
        .cancel-btn:hover { background: #bcd4ed; color: #1b407a;}
        .table-responsive { overflow-x:auto; }
        .show-password { cursor:pointer; color:#17b1c6; font-size:1.1em; margin-left:8px;}
        @media (max-width:600px) {
            .main-content { padding:10px 0;}
            .header-bar { padding:11px 7px; }
            .card-box { padding:12px 6px;}
        }
    </style>
</head>
<body>
<div class="side-nav d-none d-md-flex flex-column">
    <div class="logo"><i class="bi bi-person-workspace"></i> Admin</div>
    <ul>
        <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="teachers.php" class="active"><i class="bi bi-people"></i> Teachers</a></li>
        <li><a href="students.php"><i class="bi bi-person-badge"></i> Students</a></li>
        <li><a href="subjects.php"><i class="bi bi-journal-bookmark"></i> Subjects</a></li>
        <li><a href="attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a></li>
        <li><a href="marks.php"><i class="bi bi-bar-chart-line"></i> Marks</a></li>
        <li><a href="notices.php"><i class="bi bi-megaphone"></i> Notices</a></li>
        <li><a href="fee.php"><i class="bi bi-cash-stack"></i> Fee</a></li>
    </ul>
    <a href="../logout.php" class="btn btn-danger logout-btn d-block mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
<!-- Header for mobile -->
<div class="header-bar d-md-none">
    <span class="school-title">DAR-UL-HUDA PUBLIC SCHOOL (Admin)</span>
    <a href="../logout.php" class="admin-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
<div class="main-content">
    <div class="header-bar d-none d-md-flex">
        <span class="school-title">DAR-UL-HUDA PUBLIC SCHOOL (Admin)</span>
        <a href="../logout.php" class="admin-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    <div class="card-section">
        <h3 class="mb-3"><i class="bi bi-person-lines-fill"></i> Manage Teachers</h3>
        <?php if ($message): ?><div class="success" id="msgbox"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error" id="msgbox"><?= $error ?></div><?php endif; ?>
        <div class="row">
            <div class="col-lg-7 col-md-10 col-12 mx-auto">
                <div class="card-box">
                    <?php if ($editing && $edit_teacher): ?>
                    <!-- Edit Teacher Form -->
                    <form method="post" class="mb-2">
                        <input type="hidden" name="teacher_id" value="<?= $edit_teacher['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="fullname" class="form-control" required value="<?= htmlspecialchars($edit_teacher['fullname']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($edit_teacher['username']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_teacher['email']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class *</label>
                            <select name="class" class="form-select" required>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?= $c ?>" <?= ($edit_teacher['class'] == $c ? 'selected' : '') ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($edit_teacher['contact']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($edit_teacher['address']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)
                                <span class="show-password" onclick="togglePass('edit_pw')">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </label>
                            <input type="password" name="password" class="form-control" id="edit_pw">
                        </div>
                        <div class="d-flex gap-2">
                            <button class="submit-btn" type="submit" name="edit_teacher"><i class="bi bi-save"></i> Update</button>
                            <a href="teachers.php" class="cancel-btn"><i class="bi bi-x-lg"></i> Cancel</a>
                        </div>
                    </form>
                    <?php else: ?>
                    <!-- Add Teacher Form -->
                    <form method="post" class="mb-2">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class *</label>
                            <select name="class" class="form-select" required>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?= $c ?>"><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password *
                                <span class="show-password" onclick="togglePass('add_pw')">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </label>
                            <input type="password" name="password" class="form-control" required id="add_pw">
                        </div>
                        <button class="submit-btn" type="submit" name="add_teacher"><i class="bi bi-person-plus"></i> Add Teacher</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-box mt-4">
            <h4 class="mb-3" style="color:#2446a6;font-weight:700;">All Teachers</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle bg-white rounded shadow-sm">
                    <thead class="table-primary">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Full Name</th>
                            <th scope="col">Username</th>
                            <th scope="col">Email</th>
                            <th scope="col">Class</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Address</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($teachers as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['id']) ?></td>
                            <td><?= htmlspecialchars($t['fullname']) ?></td>
                            <td><?= htmlspecialchars($t['username']) ?></td>
                            <td><?= htmlspecialchars($t['email']) ?></td>
                            <td><?= htmlspecialchars($t['class']) ?></td>
                            <td><?= htmlspecialchars($t['contact']) ?></td>
                            <td><?= htmlspecialchars($t['address']) ?></td>
                            <td>
                                <a href="teachers.php?edit=<?= $t['id'] ?>" class="edit-btn"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a href="teachers.php?delete=<?= $t['id'] ?>" class="del-btn" onclick="return confirm('Delete this teacher?')"><i class="bi bi-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($teachers)): ?>
                        <tr><td colspan="8" class="text-center">No teachers yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <a href="dashboard.php" class="btn btn-link ps-0" style="color:#176eff;font-size:1.12em;"><i class="bi bi-arrow-left-circle"></i> Back to Dashboard</a>
    </div>
</div>
<script>
function togglePass(id) {
    var inp = document.getElementById(id);
    if (!inp) return;
    inp.type = (inp.type === 'password') ? 'text' : 'password';
}
setTimeout(function(){ var m=document.getElementById('msgbox'); if(m) m.style.display='none'; }, 2200);
</script>
</body>
</html>
