<?php
session_start();
require '../db.php';

// Check role (admin or teacher)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','teacher'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Add Notice
if (isset($_POST['add_notice'])) {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $user = $_SESSION['username'];
    if ($title && $body) {
        $stmt = $conn->prepare("INSERT INTO notices (title, body, posted_by, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $title, $body, $user);
        if ($stmt->execute()) {
            $message = "Notice added!";
        } else {
            $error = "Error adding notice.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// Edit Notice
if (isset($_POST['edit_notice'])) {
    $id = (int)$_POST['notice_id'];
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($title && $body) {
        $stmt = $conn->prepare("UPDATE notices SET title=?, body=? WHERE id=?");
        $stmt->bind_param("ssi", $title, $body, $id);
        if ($stmt->execute()) {
            $message = "Notice updated!";
        } else {
            $error = "Error updating notice.";
        }
    } else {
        $error = "All fields required.";
    }
}

// Delete Notice
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM notices WHERE id=$id");
    $message = "Notice deleted!";
}

// Edit Form Data
$editing = false;
$edit_notice = [];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $result = $conn->query("SELECT * FROM notices WHERE id=$edit_id LIMIT 1");
    if ($result && $result->num_rows == 1) {
        $editing = true;
        $edit_notice = $result->fetch_assoc();
    }
}

// Fetch Notices (latest first)
$notices = $conn->query("SELECT * FROM notices ORDER BY date DESC")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice Board</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    body { background: #f3f6fa; font-family: 'Roboto', Arial, sans-serif; }
    .notices-content {
        max-width: 1050px;
        margin: 28px auto 24px auto;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 18px #aacbf022;
        padding: 32px 18px 25px 18px;
    }
    h2, h3 { color:#204982; font-family: 'Montserrat', sans-serif; font-weight:800;}
    .form-label { font-weight: 600; color: #2446a6; }
    .form-control, textarea {
        border-radius: 7px !important;
        border: 1.2px solid #b5c9e6;
    }
    textarea { min-height: 78px; }
    .btn-primary, .submit-btn {
        background: #437ef7; color: #fff; border: none;
        border-radius: 7px; font-weight: 600; padding: 11px 27px;
        transition: background .14s;
        box-shadow: 0 1px 4px #1849a630;
    }
    .btn-primary:hover, .submit-btn:hover { background: #2446a6; }
    .btn-warning { background: #ffe488; color: #2446a6; font-weight:600;}
    .btn-warning:hover { background: #ffd236; color: #1b2746;}
    .edit-btn, .delete-btn {
        padding: 8px 17px; border: none; border-radius: 7px; margin: 0 2px; font-weight: 600; cursor: pointer; font-size:0.98em;
        min-width: 74px;
    }
    .edit-btn { background: #ffd350; color: #322600;}
    .edit-btn:hover { background: #ffb800; color: #fff;}
    .delete-btn { background: #ea4444; color: #fff;}
    .delete-btn:hover { background: #bb2a2a;}
    .success { color: #168c34; background: #d1f9e8; border-radius: 5px; padding: 11px 18px; margin-bottom: 14px;}
    .error { color: #c60000; background: #ffd3d3; border-radius: 5px; padding: 11px 18px; margin-bottom: 14px;}
    .table-responsive { margin-bottom: 20px; }
    th { background: #f4f9ff !important; color: #2446a6 !important; }
    td, th { vertical-align: middle !important; word-break:break-word;}
    @media (max-width: 700px) {
        .notices-content { padding: 13px 2vw; margin: 14px 1vw; }
        .table { font-size: 0.97em; }
        .edit-btn, .delete-btn { padding: 8px 7px; font-size:0.97em; min-width:64px;}
    }
    @media (max-width: 480px) {
        .notices-content { padding:7px 1vw;}
        h2 { font-size: 1.13em; }
        .table { font-size: 0.95em;}
    }
    /* Action buttons: stack on mobile */
    @media (max-width: 480px) {
        .action-btns { display: flex; flex-direction:column; gap:6px; }
    }
    </style>
</head>
<body>
    <div class="notices-content shadow">
        <h2 class="mb-4 d-flex align-items-center"><i class="bi bi-megaphone-fill me-2"></i> Notice Board</h2>
        <?php if ($message): ?><div class="success" id="msgbox"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error" id="msgbox"><?= $error ?></div><?php endif; ?>

        <div class="mb-4">
        <?php if ($editing): ?>
            <!-- Edit Notice Form -->
            <form method="post" class="mb-3">
                <input type="hidden" name="notice_id" value="<?= $edit_notice['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($edit_notice['title']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Body *</label>
                    <textarea name="body" class="form-control" required><?= htmlspecialchars($edit_notice['body']) ?></textarea>
                </div>
                <button class="btn btn-primary me-2" type="submit" name="edit_notice"><i class="bi bi-pencil-square"></i> Update</button>
                <a href="notices.php" class="btn btn-warning text-dark">Cancel</a>
            </form>
        <?php else: ?>
            <!-- Add Notice Form -->
            <form method="post" class="mb-3">
                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Body *</label>
                    <textarea name="body" class="form-control" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit" name="add_notice"><i class="bi bi-plus-circle"></i> Add Notice</button>
            </form>
        <?php endif; ?>
        </div>

        <h3 class="mb-3">All Notices</h3>
        <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
            <tr>
                <th style="min-width:90px;">Title</th>
                <th>Body</th>
                <th style="min-width:105px;">Date</th>
                <th style="min-width:88px;">Posted By</th>
                <th style="min-width:115px;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($notices as $n): ?>
            <tr>
                <td><?= htmlspecialchars($n['title']) ?></td>
                <td><?= nl2br(htmlspecialchars($n['body'])) ?></td>
                <td>
                    <?= date("d M Y, h:i A", strtotime($n['date'])) ?>
                </td>
                <td><?= htmlspecialchars($n['posted_by']) ?></td>
                <td>
                    <div class="action-btns">
                        <a href="notices.php?edit=<?= $n['id'] ?>" class="edit-btn btn btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                        <a href="notices.php?delete=<?= $n['id'] ?>" class="delete-btn btn btn-sm" onclick="return confirm('Delete this notice?')"><i class="bi bi-trash"></i> Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($notices)): ?>
            <tr><td colspan="5" class="text-center text-muted">No notices yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <a href="dashboard.php" class="btn btn-link" style="color:#437ef7;font-size:1.09em;"><i class="bi bi-arrow-left-circle"></i> Back to Dashboard</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-hide alerts
    setTimeout(function(){ var m=document.getElementById('msgbox'); if(m) m.style.display='none'; }, 2200);
    </script>
</body>
</html>
