<?php
ini_set("session.cookie_httponly", 1);
ini_set("session.use_strict_mode", 1);

session_start();
require "db.php";

if(empty($_SESSION['csrf'])){
    $_SESSION['csrf'] = bin2hex(random_bytes(32)); 
}

$message="";


if(isset($_POST['login'])){

    if(!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])){
        die("Invalid Request");
    }


    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? '';
 
    $query=$conn->prepare("SELECT * FROM users WHERE user_id=?  AND role=?  AND status='active' LIMIT 2" );
    $query->execute([$user_id,$role]);
    $user=$query->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password,$user['password'])){

        session_regenerate_id(true);
        $_SESSION['user_id']=$user['user_id'];
        $_SESSION['role']=$user['role'];
        $_SESSION['fullname']=$user['fullname'];

        $log=$conn->prepare(
            "INSERT INTO login_history 
            (user_id, fullname, role, ip_address, device_info, login_status)
            VALUES(?,?,?,?,?,?)"
        );


        $log->execute([$user['user_id'], $user['fullname'], $user['role'],$_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'],"SUCCESS"]);

        if(($user['role'])=="student"){
            $_SESSION['student_id']=$user['user_id'];
            header("Location:StudentPortal/student_dashboard.php");
            exit;
        }elseif($user['role']=="admin"){
            $_SESSION['adviser_id']=$user['user_id'];
            header("Location:AdminPortal/admin_dashboard.php");
            exit;
        }elseif($user['role']=="teacher"){
            $_SESSION['teacher_id']=$user['user_id'];
            header("Location:TeacherPortal/teacher_dashboard.php");
            exit;
        }else{

        header("Location:index.php");
        exit;

        }

        


    }else{


        $message="Invalid User ID, Password, or Account Type";


        $log=$conn->prepare("INSERT INTO login_history(user_id, ip_address, device_info, login_status) VALUES(?,?,?,?)" );
        $log->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], "FAILED" ]);

    }

}

?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Portal Login</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{
    --purple:#764ba2;
    --purple-dark:#633d8a;
    --purple-soft:#f1ecf8;
    --ink:#14142b;
    --muted:#7b8099;
    --line:#eceef6;
    --field:#f4f5fa;
}
*{ box-sizing:border-box; }

body{
    min-height:100vh;
    margin:0;
    padding:20px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#f6f7fc;
    color:var(--ink);
    font-family:"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif;
}

/* Card */
.login-card{
    width:100%;
    max-width:430px;
    padding:38px 34px 34px;
    background:#fff;
    border:1px solid var(--line);
    border-radius:28px;
    box-shadow:0 18px 50px rgba(34,40,90,.08);
}

/* Brand */
.brand{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    margin-bottom:26px;
}
.brand-icon{
    width:48px; height:48px;
    border-radius:15px;
    background:var(--purple-soft);
    color:var(--purple);
    display:flex; align-items:center; justify-content:center;
    font-size:24px;
}
.brand-name{ font-size:24px; font-weight:800; letter-spacing:-.3px; }

h2{
    margin:0 0 6px;
    text-align:center;
    font-size:26px;
    font-weight:800;
    letter-spacing:-.4px;
}
.sub{
    margin:0 0 26px;
    text-align:center;
    color:var(--muted);
    font-size:15px;
}

/* Role switch */
.role-toggle{
    display:flex;
    gap:6px;
    padding:6px;
    margin-bottom:22px;
    background:var(--field);
    border-radius:18px;
}
.role-toggle button{
    flex:1;
    height:44px;
    display:flex; align-items:center; justify-content:center; gap:7px;
    border:none;
    border-radius:13px;
    background:transparent;
    color:var(--muted);
    font:inherit;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:.25s;
}
.role-toggle button:hover{ color:var(--purple); }
.role-toggle button.active{
    background:var(--purple);
    color:#fff;
    box-shadow:0 8px 18px rgba(118,75,162,.28);
}

/* Fields */
.field{ position:relative; margin-bottom:14px; }
.field i{
    position:absolute;
    left:18px; top:50%;
    transform:translateY(-50%);
    color:#9a9eb5;
    font-size:17px;
    pointer-events:none;
}
.field input{
    width:100%;
    height:52px;
    padding:0 18px 0 48px;
    border:2px solid transparent;
    border-radius:16px;
    background:var(--field);
    color:var(--ink);
    font:inherit;
    font-size:15px;
    outline:none;
    transition:.2s;
}
.field input::placeholder{ color:#9a9eb5; }
.field input:focus{
    background:#fff;
    border-color:var(--purple);
    box-shadow:0 0 0 4px rgba(118,75,162,.12);
}
.field:focus-within i{ color:var(--purple); }

/* Button */
.btn-login{
    width:100%;
    height:52px;
    margin-top:8px;
    display:flex; align-items:center; justify-content:center; gap:8px;
    border:none;
    border-radius:16px;
    background:var(--purple);
    color:#fff;
    font:inherit;
    font-size:15px;
    font-weight:800;
    letter-spacing:.4px;
    cursor:pointer;
    box-shadow:0 12px 24px rgba(118,75,162,.28);
    transition:.25s;
}
.btn-login:hover{ background:var(--purple-dark); transform:translateY(-2px); }
.btn-login:active{ transform:none; }

/* Error */
.alert-error{
    display:flex; align-items:center; gap:10px;
    margin-bottom:18px;
    padding:13px 16px;
    border-radius:14px;
    background:#fee2e2;
    color:#991b1b;
    font-size:14px;
    font-weight:600;
}

.foot{ margin:24px 0 0; text-align:center; color:var(--muted); font-size:13px; }

@media (max-width:420px){
    .login-card{ padding:30px 22px 26px; border-radius:24px; }
    .role-toggle button{ font-size:13px; gap:5px; }
}
</style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <span class="brand-icon"><i class="bi bi-shield-lock-fill"></i></span>
            <span class="brand-name">Portal</span>
        </div>

        <h2>Welcome back</h2>
        <p class="sub">Choose your account type and sign in.</p>

<?php
    if($message){ echo "<div class='alert-error'><i class='bi bi-exclamation-circle-fill'></i><span>" . htmlspecialchars($message) . "</span></div>"; }
?>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf']; ?>">
            <input type="hidden" name="role" id="role" value="student">

            <div class="role-toggle">
                <button type="button" class="active" onclick="selectRole('student',this)"><i class="bi bi-person"></i>Student</button>
                <button type="button" onclick="selectRole('admin',this)"><i class="bi bi-person-badge"></i>Admin</button>
                <button type="button" onclick="selectRole('teacher',this)"><i class="bi bi-mortarboard"></i>Teacher</button>
            </div>

            <div class="field">
                <i class="bi bi-person"></i>
                <input name="user_id" placeholder="User ID" autocomplete="username" required>
            </div>
            <div class="field">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
            </div>

            <button class="btn-login" name="login">LOGIN <i class="bi bi-arrow-right"></i></button>
        </form>

        <p class="foot">&copy; <?= date('Y') ?> Portal. All rights reserved.</p>
    </div>

<script>
function selectRole(role, button){
    document.getElementById("role").value = role;
    document.querySelectorAll(".role-toggle button").forEach(function(btn){ btn.classList.remove("active"); });
    button.classList.add("active");
}
</script>
</body>
</html>