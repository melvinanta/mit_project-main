<?php


function page_top(string $title, string $active): void {
    global $fullname, $unreadCount, $pendingCount;

  
    $links = [
        ['Dashboard',     'teacher_dashboard.php', 'bi-grid-fill',         0],
        ['Sections',      'sections.php',          'bi-diagram-3',         0],
        ['Students',      'students.php',          'bi-people',            0],
        ['Requests',      'requests.php',          'bi-file-earmark-check', $pendingCount],
        ['Grades',        'grades.php',            'bi-clipboard-data',    0],
        ['Reports',       'reports.php',           'bi-bar-chart-line',    0],
        ['Notifications', 'notifications.php',     'bi-bell',              $unreadCount],
    ];

    $flash = flash_get();
    $cred  = $_SESSION['cred'] ?? null;
    unset($_SESSION['cred']);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($title) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
<link href="../sidebar.css" rel="stylesheet">
<link href="../student.css" rel="stylesheet">
<link href="../teacher.css" rel="stylesheet">
</head>
<body>


<aside class="sidebar" id="sidebar">
    <a class="sb-brand" href="teacher_dashboard.php">
        <i class="bi bi-shield-lock-fill"></i><span class="label">Portal</span>
    </a>

    <ul class="sb-nav">
        <?php foreach ($links as $l): ?>
        <li>
            <a href="<?= e($l[1]) ?>" title="<?= e($l[0]) ?>" class="<?= $active === $l[1] ? 'active' : '' ?>">
                <i class="bi <?= e($l[2]) ?>"></i><span class="label"><?= e($l[0]) ?></span>
                <?php if ($l[3] > 0): ?>
                    <span class="nbadge label"><?= $l[3] > 99 ? '99+' : (int)$l[3] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sb-footer">
        <div class="sb-user">
            <i class="bi bi-person-circle"></i>
            <div class="label">
                <strong><?= e($fullname) ?></strong>
                <small>Teacher</small>
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

<!-- CONTENT -->
<div class="content">
    <header class="topbar">
        <button class="sb-toggle" id="sbToggle" type="button" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="title"><?= e($title) ?></span>
        <span class="date"><?= date('l, F j, Y') ?></span>
    </header>

    <main class="page">
<?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> glass-alert mt-3"><?= nl2br(e($flash['msg'])) ?></div>
<?php endif; ?>
<?php if ($cred): ?>
        <div class="glass section cred-card mt-3">
            <h5><i class="bi bi-key me-2"></i>Login credentials</h5>
            <div class="cred-grid">
                <div><small>Student</small><strong><?= e($cred['name']) ?></strong></div>
                <div><small>Student ID</small><strong class="mono"><?= e($cred['id']) ?></strong></div>
                <div><small>Password</small><strong class="mono"><?= e($cred['password']) ?></strong></div>
            </div>
            <p class="small mb-0 opacity-75">Give this to the student now. Passwords are stored encrypted and can't be shown again &mdash; use "Reset password" to issue a new one.</p>
        </div>
<?php endif; ?>
<?php
}

function page_bottom(): void {
    ?>
        <footer class="text-center mt-5 mb-3">&copy; <?= date('Y') ?> Portal. All rights reserved.</footer>
    </main>
</div>

<script>
(function(){
    var body = document.body;
    var isMobile = function(){ return window.innerWidth < 992; };

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
<?php
}