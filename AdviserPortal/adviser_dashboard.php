<?php


ini_set("session.cookie_httponly", 1);
ini_set("session.use_strict_mode", 1);

session_start();
require __DIR__ . "/../db.php";   


if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'adviser') {
    header("Location: ../index.php");
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$fullname  = $_SESSION['fullname'] ?? 'User';
$roleLabel = 'Adviser';


$links = [
    ['Dashboard',  'adviser_dashboard.php', 'bi-grid-fill'],
    ['Test 1',   'test1.php',          'bi-people'],
    ['Test 2', 'test2.php',        'bi-calendar-check'],
    ['Test 3',    'test3.php',           'bi-file-earmark-text'],
    ['Test 4',   'test4.php',          'bi-chat-dots'],
];
$current = basename($_SERVER['PHP_SELF']);


$stats = [
    ['Test 1',        35, 'bi-people'],
    ['Test 2',   32, 'bi-calendar-check'],
    ['Test 3',    3,  'bi-person-x'],
    ['Test 4', 5,  'bi-file-earmark-text'],
];
$quick = [
    ['Sample 1', 'sample1.php', 'bi-calendar-check'],
    ['Sample 2',   'sample2.php',   'bi-people'],
    ['Sample 3',    'sample3.php',   'bi-chat-dots'],
];
$rows = [];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adviser Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="../style.css" rel="stylesheet">

</head>
<body>


<aside class="sidebar" id="sidebar">
    <a class="sb-brand" href="adviser_dashboard.php">
        <i class="bi bi-shield-lock-fill"></i><span class="label">Portal</span>
    </a>

    <ul class="sb-nav">
        <?php foreach ($links as $l): ?>
        <li>
            <a href="<?= e($l[1]) ?>" title="<?= e($l[0]) ?>" class="<?= $current === $l[1] ? 'active' : '' ?>">
                <i class="bi <?= e($l[2]) ?>"></i><span class="label"><?= e($l[0]) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sb-footer">
        <div class="sb-user">
            <i class="bi bi-person-circle"></i>
            <div class="label">
                <strong><?= e($fullname) ?></strong>
                <small><?= e($roleLabel) ?></small>
            </div>
        </div>
        <form method="POST" action="../logout.php" class="m-0">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <button class="sb-logout" title="Logout">
                <i class="bi bi-box-arrow-right"></i><span class="label">Logout</span>
            </button>
        </form>
    </div>
</aside>
<div class="sb-overlay" id="sbOverlay"></div>


<div class="content">
    <header class="topbar">
        <button class="sb-toggle" id="sbToggle" type="button" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="title"><?= e($roleLabel) ?> Dashboard</span>
        <span class="date"><?= date('l, F j, Y') ?></span>
    </header>

    <main class="page">

        <div class="welcome my-4">
            <h2>Welcome, <?= e($fullname) ?></h2>
  
        </div>

        <div class="row g-3 mb-4">
            <?php foreach ($stats as $s): ?>
            <div class="col-6 col-xl-3">
                <div class="glass stat-card">
                    <i class="bi <?= e($s[2]) ?>"></i>
                    <div class="num"><?= e($s[1]) ?></div>
                    <div class="lbl"><?= e($s[0]) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="glass section h-100">
                    <h5>Quick Actions</h5>
                    <div class="d-grid gap-3">
                        <?php foreach ($quick as $q): ?>
                        <a class="quick" href="<?= e($q[1]) ?>">
                            <i class="bi <?= e($q[2]) ?> fs-5"></i><?= e($q[0]) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <h5>Recent Activity</h5>
                    <div class="table-responsive">
                        <table class="table table-glass align-middle">
                            <thead>
                                <tr><th>Activity</th><th>Details</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= e($r[0]) ?></td>
                                    <td><?= e($r[1]) ?></td>
                                    <td><?= e($r[2]) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center mt-5 mb-3">&copy; <?= date('Y') ?> All rights reserved.</footer>
    </main>
</div>

<script>
(function(){
    var body = document.body;
    var isMobile = function(){ return window.innerWidth < 992; };

    // Restore collapsed state on desktop
    try {
        if (!isMobile() && localStorage.getItem('sbCollapsed') === '1') {
            body.classList.add('sb-collapsed');
        }
    } catch(e) {}

    document.getElementById('sbToggle').addEventListener('click', function(){
        if (isMobile()) {
            body.classList.toggle('sb-open');
        } else {
            body.classList.toggle('sb-collapsed');
            try {
                localStorage.setItem('sbCollapsed', body.classList.contains('sb-collapsed') ? '1' : '0');
            } catch(e) {}
        }
    });

    document.getElementById('sbOverlay').addEventListener('click', function(){
        body.classList.remove('sb-open');
    });
})();
</script>
</body>
</html>