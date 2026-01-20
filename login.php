<?php
session_start();
require 'db.php'; // adjust path if needed
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            // Redirect by role
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            } elseif ($user['role'] == 'teacher') {
                header('Location: teacher/dashboard.php');
                exit;
            } elseif ($user['role'] == 'student') {
                header('Location: student/dashboard.php');
                exit;
            }
            header('Location: index.php');
            exit;
        }
    }
    $error = 'Invalid credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - DAR-UL-HUDA PUBLIC SCHOOL</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Montserrat', 'Roboto', Arial, sans-serif;
            background: linear-gradient(125deg, #f7fafd 70%, #e3f2fd 100%);
        }
        .login-bg {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        /* NEW FACEBOOK IMAGE as background */
        background: url('https://scontent.fskt16-1.fna.fbcdn.net/v/t39.30808-6/517404918_2355558554837857_6770161158596721784_n.jpg?stp=dst-jpg_s960x960_tt6&_nc_cat=107&ccb=1-7&_nc_sid=833d8c&_nc_ohc=Kzo4og4xNJUQ7kNvwGfSpFv&_nc_oc=AdlyikNg_8o2hBNEfjryUgBIgSV7xqTkQUuI8JDe9LljEFdfApa2ro0fDL3z54oa5SQ&_nc_zt=23&_nc_ht=scontent.fskt16-1.fna&_nc_gid=CLysSG7lhSDoCbBzaaumZg&oh=00_AfQsx9GKaqc_js3MaJ0x0ly2WNDhYJmrLcZczoNH10w2Kg&oe=6872F984') center center/cover no-repeat;
        z-index: 0;
        opacity: 0.15; /* keep this for nice form contrast */
    }
        .login-card {
            border-radius: 16px;
            box-shadow: 0 8px 40px #91a7c526;
            background: rgba(255,255,255,0.97);
        }
        .login-logo {
            height: 55px;
            margin-bottom: 12px;
        }
        .navbar-brand span {
            color: #1849a6;
            font-weight: 700;
            font-family: 'Montserrat', Arial, sans-serif;
            letter-spacing: 1px;
            font-size: 1.1em;
        }
        .form-control:focus {
            border-color: #437ef7;
            box-shadow: 0 2px 10px #437ef72a;
        }
        .login-btn-main {
            background: #437ef7;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            padding: 12px 0;
            transition: background .17s;
            box-shadow: 0 2px 10px #437ef724;
        }
        .login-btn-main:hover {
            background: #1849a6;
        }
        .error {
            color: #fff;
            background: #e74c3c;
            padding: 8px 0;
            margin-bottom: 10px;
            text-align: center;
            border-radius: 6px;
            font-size: 1em;
            letter-spacing: .2px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3 mb-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <img src="images/logo.png" alt="School Logo" style="height:38px; border-radius:7px; box-shadow:0 2px 8px #b5cbe722;">
                <span>DAR-UL-HUDA PUBLIC SCHOOL</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <ul class="navbar-nav gap-lg-2">
                    <li class="nav-item">
                     
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- BG illustration -->
    <div class="login-bg"></div>
    <!-- Login Card -->
    <div class="container d-flex align-items-center justify-content-center" style="min-height:80vh;z-index:1;position:relative;">
        <div class="col-lg-4 col-md-6 col-12 px-1">
            <div class="login-card p-4 p-md-5 mx-auto mt-4">
                <div class="text-center mb-3">
                    <img src="images/logo.png" class="login-logo" alt="Logo">
                    <h3 class="fw-bold mb-1" style="color:#1849a6;">Welcome Back!</h3>
                    <div class="mb-2" style="color:#528ad6;font-size:.97em;">Please login to continue</div>
                </div>
                <form class="login-form" action="login.php" method="post" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label" for="username"><span class="me-2">&#9993;</span>Username</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required autofocus>
                    </div>
                    <div class="mb-2 position-relative">
                        <label class="form-label" for="password"><span class="me-2">&#128274;</span>Password</label>
                        <input type="password" name="password" id="password-field" class="form-control" placeholder="Enter your password" required>
                        <span class="position-absolute end-0 top-50 translate-middle-y me-3" style="cursor:pointer;color:#aaa;font-size:1.1em;" onclick="togglePassword()" title="Show/Hide password">&#128065;</span>
                    </div>
                    <div class="mb-2 text-end">
                        <a href="index.php" class="link-primary" style="font-size:0.97em;">&larr; Back to Home</a>
                    </div>
                    <?php if ($error): ?>
                        <div class="error mb-2"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <button type="submit" class="btn login-btn-main w-100 mt-2">Login</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword() {
        var x = document.getElementById("password-field");
        x.type = (x.type === "password") ? "text" : "password";
    }
    </script>
</body>
</html>
