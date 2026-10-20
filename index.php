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
            header("Location:dashboard.php");
            exit;
        }elseif($user['role']=="adviser"){
            $_SESSION['adviser_id']=$user['user_id'];
            header("Location:AdviserPortal/adviser_dashboard.php");
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
<html>
<head>
<title>Portal Login</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>

body{
    min-height:100vh;
    background: linear-gradient(135deg,#667eea,#764ba2);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}
.login-card{
    max-width:420px;
    width:100%;
    padding:35px;
    background:rgba(255,255,255,.18);
    backdrop-filter:blur(15px);
    border-radius:25px;
    box-shadow:0 20px 50px #0005;
    color:white;
}
.logo{
    text-align:center;
    font-size:70px;
}
input{
    height:50px;
    border-radius:15px!important;
}
.btn-login{
    height:50px;
    border-radius:15px;
    font-weight:bold;
}
.role-toggle{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}
.role-toggle button{
    flex:1;
    height:45px;
    border-radius:15px;
    border:none;
    font-weight:bold;
    transition:.3s;
}
.role-toggle button.active{
    background:white;
    color:#764ba2;
    box-shadow:0 5px 15px #0003;
}
.role-toggle button:not(.active){
    background:rgba(255,255,255,.25);
    color:white;
}
</style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
            <h2 class="text-center"> Login Portal</h2>
            <p class="text-center"> Login Account </p>
<?php
    if($message){ echo " <div class='alert alert-danger'> $message </div>";}
?>

<form method="POST">
    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf']; ?>">
    <input type="hidden" name="role"  id="role" value="student">
        <div class="role-toggle">
            <button type="button"class="active" onclick="selectRole('student',this)">
                <i class="bi bi-person"></i>Student</button>
            <button type="button" onclick="selectRole('adviser',this)">
                <i class="bi bi-person-badge"></i>Adviser </button>
            <button type="button" onclick="selectRole('teacher',this)">
                <i class="bi bi-mortarboard"></i>Teacher</button>
        </div>
    <input class="form-control mb-3" name="user_id" placeholder="User ID"required>
    <input class="form-control mb-3" type="password" name="password" placeholder="Password" required>
            <button class="btn btn-light w-100 btn-login" name="login"> LOGIN
                <i class="bi bi-arrow-right"></i></button>
</form>
    </div>
<script>
function selectRole(role,button){ document.getElementById("role").value=role;
    let buttons=document.querySelectorAll(".role-toggle button");
        buttons.forEach(btn=>{ btn.classList.remove("active"); });
        button.classList.add("active");
}
</script>
</body>
</html>