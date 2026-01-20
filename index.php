<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DAR-UL-HUDA PUBLIC SCHOOL - Student Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="images/logo.png">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Roboto', Arial, sans-serif;
            background: linear-gradient(120deg, #e7f5ff 0%, #d1fffc 100%);
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 0;
            background: radial-gradient(ellipse at top right, #87e1f7 0%, #fff0 75%),
                        radial-gradient(ellipse at bottom left, #84ecb833 0%, #fff0 75%);
            opacity: 0.37;
            pointer-events: none;
        }
        .main-container {
            min-height: 90vh;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 32px;
            padding: 40px 0.5in 35px 0.5in; /* <-- 0.5 inch left/right */
            z-index: 2;
            position: relative;
        }
        @media (max-width: 1100px) {
            .main-container {
                flex-direction: column-reverse;
                padding: 20px 0.5in 10px 0.5in;
                gap: 18px;
            }
        }
        @media (max-width: 900px) {
            .main-container {
                padding-left: 18px;
                padding-right: 18px;
            }
        }
        @media (max-width: 600px) {
            .main-container {
                padding-left: 8px;
                padding-right: 8px;
            }
        }
        .welcome-section {
            background: rgba(255,255,255,0.93);
            border-radius: 22px;
            box-shadow: 0 8px 34px #b5cbe636, 0 2px 12px #63e9e636;
            padding: 46px 38px 38px 46px;
            max-width: 520px;
            flex: 1 1 400px;
            margin-bottom: 20px;
            position: relative;
            border-left: 7px solid #38dbc7;
            border-top: 4px solid #318cf6;
            backdrop-filter: blur(2px);
            transition: box-shadow .22s;
            animation: fadeInUp 1.1s cubic-bezier(.21,1.01,.36,1) both;
        }
        .welcome-section:hover {
            box-shadow: 0 18px 36px #1de4c980, 0 6px 32px #57f3c222;
        }
        .welcome-section h1 {
            font-family: 'Montserrat', Arial, sans-serif;
            color: #1849a6;
            font-size: 2.6rem;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: 1px;
            text-shadow: 0 2px 12px #c5eafd14;
            transition: color .17s;
        }
        .welcome-section h1 span {
            font-size: 1.2em;
        }
        .welcome-section h2 {
            color: #13b27a;
            font-weight: 700;
            font-size: 1.33rem;
            margin-bottom: 12px;
            margin-top: 12px;
            letter-spacing: .7px;
            text-shadow: 0 2px 8px #77ffdb13;
        }
        .subtitle {
            font-size: 1.1rem;
            color: #496091;
            margin-bottom: 38px;
            line-height: 1.6;
            letter-spacing: .01em;
        }
        .get-started {
            display: inline-block;
            padding: 13px 44px;
            background: linear-gradient(90deg, #318cf6 65%, #22dec1 100%);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 13px;
            font-size: 1.13em;
            text-decoration: none;
            letter-spacing: 0.7px;
            box-shadow: 0 2px 14px #7ae9b91a, 0 2px 8px #38caf824;
            transition: background .16s, transform .13s, box-shadow .16s;
            outline: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .get-started:after {
            content: "";
            position: absolute;
            left: 50%; top: 50%;
            width: 150%; height: 150%;
            background: radial-gradient(circle, #72ffe6 0 16%, #318cf633 60%, transparent 100%);
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8);
            z-index: 0;
            transition: opacity .22s, transform .22s;
            pointer-events: none;
        }
        .get-started:hover, .get-started:focus {
            background: linear-gradient(90deg,#2446a6 60%,#19c5b1 100%);
            color: #fff;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 10px 40px #44f7e151, 0 2px 18px #318cf622;
        }
        .get-started:hover:after, .get-started:focus:after {
            opacity: .18;
            transform: translate(-50%, -50%) scale(1);
        }
        .illustration {
            flex: 1 1 320px;
            min-width: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            z-index: 2;
            animation: fadeInUp 1.2s .08s both;
        }
        .illustration img {
            max-width: 99%;
            width: 420px;
            min-width: 150px;
            border-radius: 18px;
            box-shadow: 0 7px 24px #afd8ef32, 0 1px 7px #21e2af24;
            background: #fafdff;
            transition: box-shadow .17s, transform .13s;
            filter: drop-shadow(0 2px 8px #a3f6f154);
        }
        .illustration img:hover {
            box-shadow: 0 14px 48px #50d6e233, 0 2px 11px #39dbb339;
            transform: scale(1.04) rotate(-2deg);
            filter: brightness(1.06);
        }
        @keyframes fadeInUp {
            0% { opacity:0; transform: translateY(38px);}
            100% { opacity:1; transform: none;}
        }
        @media (max-width: 700px) {
            .welcome-section {
                padding: 15px 3vw 16px 3vw;
                font-size: 0.97em;
                border-radius: 16px;
            }
            .welcome-section h1 {
                font-size: 1.6em;
            }
            .welcome-section h2 {
                font-size: 1.08em;
            }
            .get-started {
                width: 100%;
                font-size: 1em;
                padding: 12px 2vw;
                border-radius: 10px;
            }
            .main-container {
                gap: 10px;
            }
            .illustration img {
                margin-bottom: 10px;
                box-shadow: 0 1px 7px #b8e1fd33;
            }
        }
        @media (max-width: 520px) {
            .welcome-section {
                border-left-width: 3.5px;
                border-top-width: 2px;
                padding: 10px 1vw 13px 2vw;
            }
            .illustration img {
                border-radius: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <div class="welcome-section shadow-lg">
            <h1>
                <span>🎓</span>
                Welcome To The Student<br>
                Management System
            </h1>
            <h2>Empowering Education, Enabling Success</h2>
            <div class="subtitle mb-3">
                Success is not the key to happiness.<br>
                <span class="d-none d-sm-inline">Happiness is the key to success.</span>
                If you love what you are doing, you will be successful.
            </div>
            <?php if (!isset($_SESSION['username'])): ?>
                <a href="login.php" class="get-started">Get Started</a>
            <?php else: ?>
                <?php
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'admin') {
                        echo '<a href="admin/dashboard.php" class="get-started">Go to Admin Dashboard</a>';
                    } elseif ($_SESSION['role'] === 'teacher') {
                        echo '<a href="teacher/dashboard.php" class="get-started">Go to Teacher Dashboard</a>';
                    } elseif ($_SESSION['role'] === 'student') {
                        echo '<a href="student/dashboard.php" class="get-started">Go to Student Dashboard</a>';
                    }
                } else {
                    echo '<a href="logout.php" class="get-started">Logout</a>';
                }
                ?>
            <?php endif; ?>
        </div>
        <div class="illustration">
            <img src="images/illustration.png" alt="Kids reading books illustration" loading="lazy">
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <!-- Bootstrap JS (optional, for responsive nav etc) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
