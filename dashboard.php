<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['username'];
$teacher = $conn->query("SELECT * FROM users WHERE username='$username' LIMIT 1")->fetch_assoc();
$teacherName = $teacher['fullname'] ?? $teacher['username'];
$teacherClass = $teacher['class'] ?? '';
$teacherEmail = $teacher['email'] ?? '';

$pass_msg = '';
if (isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if (!password_verify($old, $teacher['password'])) {
        $pass_msg = "<div class='alert alert-danger mt-2'>Old password is incorrect.</div>";
    } elseif (strlen($new) < 5) {
        $pass_msg = "<div class='alert alert-danger mt-2'>New password must be at least 5 characters.</div>";
    } elseif ($new !== $confirm) {
        $pass_msg = "<div class='alert alert-danger mt-2'>New passwords do not match.</div>";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $new_hash, $teacher['id']);
        if ($stmt->execute()) {
            $pass_msg = "<div class='alert alert-success mt-2'>Password changed successfully.</div>";
            $teacher = $conn->query("SELECT * FROM users WHERE username='$username' LIMIT 1")->fetch_assoc();
        } else {
            $pass_msg = "<div class='alert alert-danger mt-2'>Failed to update password.</div>";
        }
    }
}

// Stats
$studentCount = 0;
if ($teacherClass) {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'student' AND class='$teacherClass'");
    if ($result) $studentCount = $result->fetch_assoc()['cnt'];
} else {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'student'");
    if ($result) $studentCount = $result->fetch_assoc()['cnt'];
}
$subjectCount = 0;
if ($teacherClass) {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM subjects WHERE class='$teacherClass'");
    if ($result) $subjectCount = $result->fetch_assoc()['cnt'];
} else {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM subjects WHERE teacher_username='$username'");
    if ($result) $subjectCount = $result->fetch_assoc()['cnt'];
}
$attendanceToday = 0;
if ($teacherClass) {
    $today = date('Y-m-d');
    $q = $conn->query(
        "SELECT COUNT(*) as cnt FROM attendance
        WHERE date='$today'
        AND student_id IN (SELECT id FROM users WHERE class='$teacherClass' AND role='student')"
    );
    if ($q) $attendanceToday = $q->fetch_assoc()['cnt'];
}

// All Notices
$notices = $conn->query("SELECT * FROM notices ORDER BY date DESC")->fetch_all(MYSQLI_ASSOC);

// Check for recent promotion notices (last 7 days)
$recentPromotionNotices = [];
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
foreach ($notices as $notice) {
    if (strtolower($notice['title']) === 'student promotion notice' && $notice['date'] >= $sevenDaysAgo) {
        $recentPromotionNotices[] = $notice;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - DAR-UL-HUDA PUBLIC SCHOOL</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../images/logo.png">
    <style>
:root {
    --erp-blue: #4f8cff;      /* Brighter blue */
    --erp-green: #60d394;     /* Fresh light green */
    --erp-yellow: #fff5cc;    /* Soft pastel yellow */
    --erp-bg: #f7fafc;        /* Ultra-light gray for background */
    --erp-dark: #12326b;      /* Slightly lighter dark */
    --erp-shadow: 0 4px 24px #9bb6df33;
    --erp-card-radius: 18px;
}
html, body { min-height:100vh; background:var(--erp-bg); }
body { font-family: 'Roboto', Arial, sans-serif; }
.erp-main { min-height:100vh; display: flex; flex-direction: row; }
.erp-sidebar {
    background: linear-gradient(135deg, #eaf3ff 65%, #c7ecee 100%);
    color: #2a3761;
    min-width: 230px; max-width: 270px;
    padding: 30px 12px 24px 18px;
    border-radius: 0 var(--erp-card-radius) var(--erp-card-radius) 0;
    box-shadow: var(--erp-shadow);
    display: flex; flex-direction: column;
    min-height: 100vh;
}
.erp-logo {
    font-family: 'Montserrat', Arial, sans-serif;
    font-size: 1.3em; letter-spacing: 2px; font-weight: 700;
    margin-bottom: 18px; display: flex; align-items: center; gap: 10px;
}
.erp-logo img { height: 36px; border-radius: 8px; }
.erp-sidebar-userinfo { margin-bottom: 16px; font-size: 1em; line-height: 1.32; }
.erp-sidebar-meta { color: #7b92b2; font-size: 0.98em; }
.erp-menu { list-style: none; padding: 0; margin-bottom: 20px; }
.erp-menu li, .erp-menu a {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 8px 10px 12px;
    border-radius: 8px; color: #355079; font-size: 1.09em; margin-bottom: 5px;
    transition: background .14s, color .14s; text-decoration: none; font-weight: 500;
}
.erp-menu li.active, .erp-menu a:hover, .erp-menu a:focus {
    background: #e2eeff;
    color: #2577f6;
}
.btn-warning, .btn-warning:active {
    background: #ffe066 !important;
    color: #183764 !important;
    border: none; font-weight: 600;
}
.btn-warning:hover {
    background: #fffbe7 !important;
    color: #12326b !important;
}
.btn-danger {
    background: #f66d6d !important;
    color: #fff;
}
.btn-danger:hover {
    background: #e34b4b !important;
}
.erp-dashboard-content {
    flex: 1; padding: 40px 6vw 30px 6vw;
    background: transparent; min-height: 100vh;
}
.erp-dashboard-header {
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
    margin-bottom: 24px;
}
.erp-dashboard-welcome {
    font-size: 1.13em; font-family: 'Montserrat', Arial, sans-serif;
    color: var(--erp-blue); font-weight: 700;
}
.erp-dashboard-title {
    font-size: 2em; font-family: 'Montserrat', Arial, sans-serif;
    color: var(--erp-dark); font-weight: 800; margin-bottom: 8px;
}
.erp-dashboard-subtitle {
    color: #5da484; font-size: 1.07em; margin-bottom: 19px;
}
.erp-info-cards {
    display: flex; gap: 24px; flex-wrap: wrap;
}
.erp-info-card {
    flex: 1 1 190px; background: #fff; border-radius: var(--erp-card-radius);
    box-shadow: var(--erp-shadow);
    padding: 28px 22px 22px 22px;
    display: flex; flex-direction: column; align-items: center;
    min-width: 145px; margin-bottom: 16px; position: relative;
    transition: box-shadow .14s, transform .14s; overflow: hidden;
    animation: fadeInCard .8s cubic-bezier(.2,1.12,.38,1) both;
}
.erp-info-card:before {
    content: "";
    position: absolute; bottom: -22px; right: -22px;
    width: 75px; height: 75px;
    background: radial-gradient(circle, #bedcff44 0%, #fff0 80%);
    z-index: 0;
}
.erp-info-card .erp-card-icon {
    font-size: 2.7em;
    margin-bottom: 14px; z-index: 1;
}
.erp-info-card .erp-card-value {
    font-size: 2.1em;
    font-weight: bold;
    color: var(--erp-blue); z-index: 1;
}
.erp-info-card .erp-card-label {
    font-size: 1.15em;
    color: #6283a7;
    z-index: 1;
    margin-top: 4px;
}
.erp-info-card.yellow {
    background: var(--erp-yellow);
    color: #987a18;
}
.erp-info-card.green {
    background: #e7fcf1;
    color: #239767;
}
.erp-info-card.blue {
    background: #f2f7ff;
    color: #2463eb;
}
.erp-info-card:hover {
    box-shadow: 0 10px 38px #1e3a8a18;
    transform: translateY(-2px) scale(1.025);
}
.erp-avatar {
    text-decoration: none;
    background: #d7f9ef;
    color: #2371a6;
    border-radius: 50%; padding: 10px 15px; font-size: 2em;
    box-shadow: 0 2px 9px #69f8c721;
    transition: background .13s;
}
.erp-avatar:hover {
    background: #b0e7ff; color: #1744ae;
}
/* Modal */
#changePassModal {
    display: none; position:fixed;z-index:1100;left:0;top:0;width:100vw;height:100vh;
    background:rgba(0,0,0,0.10);align-items:center;justify-content:center;
}
#changePassModal.active { display: flex; }
#changePassModal .modal-content {
    background: #fff; padding: 27px 27px 17px 27px; border-radius: 13px;
    max-width: 350px; width:95vw; margin:7% auto; box-shadow:0 4px 38px #1e3a8a12;
    position:relative;
}
.show-hide-password {
    cursor:pointer; margin-left:-30px; color:#7ea5db; position: absolute;
    right: 30px; top: 38px; z-index: 2;
}
@keyframes fadeInCard {
    0% { opacity:0; transform:translateY(50px);}
    100% { opacity:1; transform:none;}
}
@media (max-width:1100px) {
    .erp-dashboard-content { padding: 20px 2vw 18px 2vw;}
}
@media (max-width: 800px) {
    .erp-main { flex-direction: column;}
    .erp-sidebar {
        min-width:100vw; max-width:100vw;
        border-radius:0 0 var(--erp-card-radius) var(--erp-card-radius);
        flex-direction: row; flex-wrap: wrap; align-items: center;
        padding: 13px 3vw;
    }
    .erp-sidebar-userinfo { margin-bottom:2px;}
    .erp-logo { margin-bottom:0;}
    .erp-dashboard-content { padding: 10px 0vw 15px 0vw;}
    .erp-info-card { min-width: 98vw; padding: 20px 7vw;}
}
@media (max-width:500px) {
    .erp-sidebar { padding: 7px 0vw;}
    .erp-dashboard-title { font-size: 1.3em;}
    .erp-dashboard-content { padding: 3px 0 6px 0;}
    .erp-info-card { padding: 13px 2vw;}
}
/* Removed dark mode completely for always-light theme */
</style>

</head>
<body>
<?php if (!empty($pass_msg)) echo $pass_msg; ?>
<div class="erp-main">
    <!-- Sidebar -->
    <aside class="erp-sidebar shadow-sm mb-3 mb-lg-0">
        <div class="erp-logo">
            <img src="../images/logo.png" alt="Logo">
            <span>TEACHER</span>
        </div>
        <div class="erp-sidebar-userinfo">
            <div class="fw-bold"><?= htmlspecialchars($teacherName) ?></div>
            <div class="erp-sidebar-meta mb-1">Class: <?= htmlspecialchars($teacherClass) ?: 'All' ?></div>
            <div class="erp-sidebar-meta"><?= htmlspecialchars($teacherEmail) ?></div>
        </div>
        <ul class="erp-menu">
            <li class="active"><span>🏠</span> Dashboard</li>
            <li><a href="students.php"><span>👨‍🎓</span> Students</a></li>
            <li><a href="subjects.php"><span>📚</span> Subjects</a></li>
            <li><a href="attendance.php"><span>🗓️</span> Attendance</a></li>
            <li><a href="marks.php"><span>📊</span> Marks</a></li>
            <li><a href="notices.php"><span>📢</span> Notices</a></li>
            <li><a href="salary.php"><span>💰</span> My Salary</a></li>
        </ul>
        <button type="button" class="btn btn-warning mb-2 w-100" onclick="openPassModal()">Change Password</button>
        <a href="../logout.php" class="btn btn-danger w-100">Logout</a>
        <!-- Password Modal -->
        <div id="changePassModal">
            <div class="modal-content">
                <h4 class="mb-3">Change Password</h4>
                <form method="post" autocomplete="off" class="position-relative">
                    <div class="mb-2 position-relative">
                        <input type="password" name="old_password" id="old_pw" placeholder="Old Password" required class="form-control mb-2">
                        <span class="show-hide-password" onclick="togglePassword('old_pw', this)"></span>
                    </div>
                    <div class="mb-2 position-relative">
                        <input type="password" name="new_password" id="new_pw" placeholder="New Password" required class="form-control mb-2">
                        <span class="show-hide-password" onclick="togglePassword('new_pw', this)"></span>
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="password" name="confirm_password" id="confirm_pw" placeholder="Confirm New Password" required class="form-control">
                        <span class="show-hide-password" onclick="togglePassword('confirm_pw', this)">👁️</span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-1" name="change_password">Update</button>
                    <button type="button" onclick="closePassModal()" class="btn btn-link w-100 text-danger">Cancel</button>
                </form>
            </div>
        </div>
    </aside>
    <!-- Main content -->
    <div class="erp-dashboard-content">
        <div class="erp-dashboard-header">
            <div>
                <div class="erp-dashboard-welcome">Welcome, <?= htmlspecialchars($teacherName) ?></div>
                <div class="text-muted" style="font-size:0.98em;"><?= htmlspecialchars($teacherEmail) ?></div>
            </div>
            <div>
                <a href="profile.php" class="erp-avatar" title="View Profile">👤</a>
            </div>
        </div>
        <div>
            <div class="erp-dashboard-title mb-1">Teacher Dashboard</div>
            <div class="erp-dashboard-subtitle mb-2">Quick Stats for <?= htmlspecialchars($teacherClass ?: 'all classes') ?>:</div>
            <div class="erp-info-cards mb-4">
                <div class="erp-info-card yellow shadow">
                    <div class="erp-card-icon">👨‍🎓</div>
                    <div class="erp-card-value"><?= $studentCount ?></div>
                    <div class="erp-card-label">Students</div>
                </div>
                <div class="erp-info-card green shadow">
                    <div class="erp-card-icon">📚</div>
                    <div class="erp-card-value"><?= $subjectCount ?></div>
                    <div class="erp-card-label">Subjects</div>
                </div>
                <div class="erp-info-card blue shadow">
                    <div class="erp-card-icon">🗓️</div>
                    <div class="erp-card-value"><?= $attendanceToday ?></div>
                    <div class="erp-card-label">Attendance Today</div>
                </div>
            </div>

            <!-- Promotion Notices Alert -->
            <?php if (!empty($recentPromotionNotices)): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Recent Student Promotions</h5>
                <?php foreach ($recentPromotionNotices as $notice): ?>
                <p class="mb-1"><strong><?= htmlspecialchars($notice['title']) ?>:</strong> <?= htmlspecialchars($notice['body']) ?></p>
                <small class="text-muted">Posted on: <?= htmlspecialchars(date('d M Y', strtotime($notice['date']))) ?></small>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openPassModal() {
    document.getElementById('changePassModal').classList.add('active');
    setTimeout(function(){
        let p = document.querySelector('#changePassModal input[type=password]');
        if(p) p.focus();
    },120);
}
function closePassModal() {
    document.getElementById('changePassModal').classList.remove('active');
    let inputs = document.querySelectorAll('#changePassModal input[type=password]');
    inputs.forEach(i=>i.value='');
}
// Show/hide password
function togglePassword(fieldId, btn) {
    let inp = document.getElementById(fieldId);
    if (!inp) return;
    inp.type = (inp.type === 'password') ? 'text' : 'password';
    
}
document.addEventListener('keydown',function(e){
    if(e.key==='Escape') closePassModal();
});
if (document.querySelector('.alert-success')) {
    setTimeout(function() {
        closePassModal();
    }, 1400);
}
</script>
</body>
</html>
