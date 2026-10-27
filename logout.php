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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{
    --primary:#7a4fa3;
    --primary-dark:#693f91;
    --primary-soft:#efe9f7;
    --ink:#1a1a2e;
    --muted:#8b8ba3;
    --field:#f1f2f9;
    --page:#f7f8fd;
}

body{
    min-height:100vh;
    background:var(--page);
    font-family:'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    color:var(--ink);
}

.confirm-card{
    max-width:385px;
    width:100%;
    padding:34px 30px 28px;
    text-align:center;
    background:#fff;
    border-radius:28px;
    box-shadow:0 20px 50px rgba(60,50,110,.10);
}


.brand{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    margin-bottom:26px;
}
.brand-icon{
    width:42px;
    height:42px;
    border-radius:12px;
    background:var(--primary-soft);
    color:var(--primary);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
}
.brand-name{
    font-size:24px;
    font-weight:800;
    letter-spacing:-.3px;
}

/* Text */
.confirm-card h3{
    font-weight:800;
    font-size:25px;
    letter-spacing:-.3px;
    margin-bottom:6px;
}
.confirm-card p{
    color:var(--muted);
    font-size:14.5px;
    font-weight:500;
    line-height:1.5;
}
.confirm-card p strong{
    color:var(--ink);
    font-weight:700;
}

/* Buttons */
.btn-choice{
    height:46px;
    border-radius:14px;
    font-weight:700;
    font-size:14.5px;
    border:none;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    transition:all .2s ease;
}
.btn-cancel{
    background:var(--field);
    color:#6b6b85;
    text-decoration:none;
}
.btn-cancel:hover{
    background:#e6e7f3;
    color:var(--ink);
}
.btn-yes{
    background:var(--primary);
    color:#fff;
    box-shadow:0 10px 22px rgba(122,79,163,.32);
}
.btn-yes:hover{
    background:var(--primary-dark);
    color:#fff;
    transform:translateY(-1px);
}
.btn-yes:active{
    transform:translateY(0);
}

.footer-text{
    margin-top:22px;
    margin-bottom:0;
    font-size:12.5px !important;
}
</style>
</head>
<body>
    <div class="confirm-card">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <span class="brand-name">Portal</span>
        </div>

        <h3>Log out?</h3>
        <p class="mb-4">Hi <strong><?= $fullname ?></strong>, are you sure you want to log out of your account?</p>

        <form method="POST" class="row g-2">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="confirm" value="1">
            <div class="col-6">
                <a class="btn btn-choice btn-cancel w-100" href="<?= htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            </div>
            <div class="col-6">
                <button class="btn btn-choice btn-yes w-100">Yes, Logout <i class="bi bi-arrow-right"></i></button>
            </div>
        </form>

        <p class="footer-text">&copy; <?= date('Y') ?> Portal. All rights reserved.</p>
    </div>
</body>
</html>