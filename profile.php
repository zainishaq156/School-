<?php
session_start();
require '../db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}
$username = $_SESSION['username'];
$teacher = $conn->query("SELECT * FROM users WHERE username='$username' LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f6f8fc;
            font-family: 'Roboto', Arial, sans-serif;
        }
        .profile-card {
            max-width: 500px;
            margin: 36px auto 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px #c3d4ef3b;
            padding: 34px 22px 26px 22px;
        }
        .profile-header {
            font-family: 'Montserrat', Arial, sans-serif;
            font-weight: 700;
            font-size: 1.7em;
            color: #2446a6;
            margin-bottom: 12px;
            letter-spacing: 1px;
            text-align: center;
        }
        .profile-list {
            list-style: none;
            padding: 0;
            margin: 0 0 18px 0;
        }
        .profile-list li {
            padding: 11px 0 9px 0;
            border-bottom: 1px solid #ecf0f7;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        .profile-label {
            width: 38%;
            min-width: 120px;
            font-weight: 500;
            color: #437ef7;
            font-size: 1.06em;
        }
        .profile-value {
            width: 62%;
            font-size: 1.06em;
            color: #222;
            word-break: break-word;
        }
        @media (max-width:600px) {
            .profile-card { padding: 18px 5vw; }
            .profile-header { font-size: 1.17em;}
            .profile-label, .profile-value { width: 100%; }
            .profile-list li { flex-direction: column; align-items: flex-start;}
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 14px;
            font-weight: 600;
            color: #437ef7;
            text-decoration: none;
            transition: color 0.13s;
        }
        .back-link:hover { color: #1a276b; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card shadow-sm">
            <div class="profile-header">👨‍🏫 My Profile</div>
            <ul class="profile-list">
                <li>
                    <span class="profile-label">Name:</span>
                    <span class="profile-value"><?= htmlspecialchars($teacher['fullname'] ?? $teacher['username']) ?></span>
                </li>
                <li>
                    <span class="profile-label">Email:</span>
                    <span class="profile-value"><?= htmlspecialchars($teacher['email']) ?></span>
                </li>
                <li>
                    <span class="profile-label">Username:</span>
                    <span class="profile-value"><?= htmlspecialchars($teacher['username']) ?></span>
                </li>
                <li>
                    <span class="profile-label">Contact:</span>
                    <span class="profile-value"><?= htmlspecialchars($teacher['contact'] ?? '—') ?></span>
                </li>
                <li>
                    <span class="profile-label">Address:</span>
                    <span class="profile-value"><?= htmlspecialchars($teacher['address'] ?? '—') ?></span>
                </li>
                <li>
                    <span class="profile-label">Class:</span>
                    <span class="profile-value"><?= htmlspecialchars($teacher['class'] ?? '—') ?></span>
                </li>
            </ul>
            <a class="back-link" href="dashboard.php">&larr; Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
