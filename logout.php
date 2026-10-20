<?php
ini_set("session.cookie_httponly", 1);
ini_set("session.use_strict_mode", 1);
session_start();


if (empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['confirm'], $_POST['csrf'])
    && hash_equals($_SESSION['csrf'], $_POST['csrf'])) {

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    header("Location: index.php");
    exit;
}


$dashboards = [
    // 'student'    => 'StudentPortal/student_dashboard.php',
    'adviser'    => 'AdviserPortal/adviser_dashboard.php',
    'teacher'    => 'TeacherPortal/teacher_dashboard.php',
];
$cancelUrl = $dashboards[$_SESSION['role'] ?? ''] ?? 'index.php';
$fullname  = htmlspecialchars($_SESSION['fullname'] ?? 'User', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Confirm Logout</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{
    min-height:100vh;
    background:linear-gradient(135deg,#667eea,#764ba2);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    color:white;
}
.confirm-card{
    max-width:420px;
    width:100%;
    padding:35px;
    text-align:center;
    background:rgba(255,255,255,.18);
    backdrop-filter:blur(15px);
    border-radius:25px;
    box-shadow:0 20px 50px #0005;
}
.icon{ 
    font-size:64px; 
}
.btn-choice{
    height:50px;
    border-radius:15px;
    font-weight:bold;
    border:none;
}
.btn-cancel{ 
    background:rgba(255,255,255,.25); 
    color:white; display:flex; 
    align-items:center; 
    justify-content:center; 
    text-decoration:none; 
}
.btn-cancel:hover{ 
    background:rgba(255,255,255,.4); 
    color:white; 
}
.btn-yes{ 
    background:white; 
    color:#764ba2; 
}
.btn-yes:hover{ 
    background:#f1f1f1; 
    color:#764ba2; 
    }
</style>
</head>
<body>
    <div class="confirm-card">
        <div class="icon"><i class="bi bi-box-arrow-right"></i></div>
        <h3 class="mt-2">Log out?</h3>
        <p class="mb-4">Hi <?= $fullname ?>, are you sure you want to log out of your account?</p>

        <form method="POST" class="row g-2">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="confirm" value="1">
            <div class="col-6">
                <a class="btn btn-choice btn-cancel w-100" href="<?= htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            </div>
            <div class="col-6">
                <button class="btn btn-choice btn-yes w-100">Yes, Logout</button>
            </div>
        </form>
    </div>
</body>
</html>