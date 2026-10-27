<?php


function page_top(string $title, string $active): void {
    global $fullname;

    $links = [   // [label, file, icon]
        ['Dashboard', 'admin_dashboard.php', 'bi-grid-fill'],
        ['Teachers',  'teachers.php',        'bi-person-badge'],
        ['Sections',  'sections.php',        'bi-diagram-3'],
        ['Students',  'students.php',        'bi-people'],
        ['Reports',   'reports.php',         'bi-bar-chart-line'],
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
<style>

.btn-sm-x{
    height:38px;
    padding:0 14px;
    font-size:14px
}
.btn-danger-x{
    height:46px;
    width:46px;
    border:none;
    border-radius:15px;
    background:#fee2e2;
    color:#991b1b;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    transition:.3s
}
.btn-danger-x:hover{
    background:#fecaca
}
.mono{
    font-family:Consolas,"Courier New",monospace
}
.cred-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:12px;
    margin-bottom:14px
}
.cred-grid>div{
    padding:12px 16px;
    border-radius:15px;
    background:rgba(127,127,127,.12)
}
.cred-grid small{
    display:block;
    opacity:.75
}
.cred-grid strong{
    font-size:18px;
    word-break:break-all
}
details.edit summary{
    cursor:pointer;
    font-weight:600;
    opacity:.9
}
details.edit[open] summary{
    margin-bottom:8px
}
.search-input{
    height:46px;
    border:none;
    border-radius:15px;
    flex:2 1 200px;
    min-width:0
}

.live{
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-size:14px;
    opacity:.85
}
.live i{
    width:10px;
    height:10px;
    border-radius:50%;
    background:#22c55e;
    box-shadow:0 0 0 0 rgba(34,197,94,.6);
    animation:pulse 2s infinite
}
.live.off i{
    background:#ef4444;
    animation:none
}
.live.paused i{
    background:#f59e0b;
    animation:none
}
@keyframes pulse{70%{box-shadow:0 0 0 9px rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}

.hbar{
    display:grid;
    grid-template-columns:170px 1fr 84px;
    gap:12px;
    align-items:center;
    margin-bottom:12px
}
.hbar .nm{
    font-weight:600;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis
}
.hbar .trk{
    position:relative;
    height:14px;
    border-radius:7px;
    background:rgba(127,127,127,.16)
}
.hbar .fill{
    height:100%;
    border-radius:7px;
    background:var(--accent,#764ba2);
    transition:width .6s
}
.hbar .mark{
    position:absolute;
    top:-4px;bottom:-4px;
    width:2px;
    background:currentColor;
    opacity:.45
}
.hbar .val{
    text-align:right;
    font-weight:700
}
.hbar small{
    display:block;
    opacity:.65;
    font-weight:400;
    font-size:12px
}
.minibar{
    display:inline-block;
    vertical-align:middle;
    width:70px;
    height:8px;
    border-radius:4px;
    background:rgba(127,127,127,.2);
    margin-right:8px;
    overflow:hidden
}
.minibar b{
    display:block;
    height:100%;
    background:var(--accent,#764ba2)
}
.donut-wrap{
    display:flex;
    align-items:center;
    gap:22px;
    flex-wrap:wrap
}
.legend{
    display:flex;
    flex-direction:column;
    gap:8px
}
.legend span{
    display:inline-flex;
    align-items:center;
    gap:8px
}
.legend i{
    width:12px;
    height:12px;
    border-radius:3px;
    display:inline-block
}
.achiever .pill{
    white-space:nowrap
}
.table-glass td.num,.table-glass th.num{
    text-align:right
    }
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <a class="sb-brand" href="admin_dashboard.php"><i class="bi bi-shield-lock-fill"></i><span class="label"> Admin Portal</span></a>

    <ul class="sb-nav">
        <?php foreach ($links as $l): ?>
        <li>
            <a href="<?= e($l[1]) ?>" title="<?= e($l[0]) ?>" class="<?= $active === $l[1] ? 'active' : '' ?>">
                <i class="bi <?= e($l[2]) ?>"></i><span class="label"><?= e($l[0]) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sb-footer">
        <div class="sb-user">
            <i class="bi bi-person-circle"></i>
            <div class="label"><strong><?= e($fullname) ?></strong><small>Administrator</small></div>
        </div>
        <form method="POST" action="../logout.php" class="m-0">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <button class="sb-logout" title="Logout"><i class="bi bi-box-arrow-right"></i><span class="label">Logout</span></button>
        </form>
    </div>
</aside>
<div class="sb-overlay" id="sbOverlay"></div>

<div class="content">
    <header class="topbar">
        <button class="sb-toggle" id="sbToggle" type="button" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
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
                <div><small><?= e($cred['label'] ?? 'User') ?></small><strong><?= e($cred['name']) ?></strong></div>
                <div><small>User ID</small><strong class="mono"><?= e($cred['id']) ?></strong></div>
                <div><small>Password</small><strong class="mono"><?= e($cred['password']) ?></strong></div>
            </div>
            <p class="small mb-0 opacity-75">Give this to the user now. Passwords are stored encrypted and can't be shown again &mdash; use "Reset password" to issue a new one.</p>
        </div>
<?php endif; ?>
<?php
}

function page_bottom(string $extraScript = ''): void {
    ?>
        <footer class="text-center mt-5 mb-3">&copy; <?= date('Y') ?> Portal. All rights reserved.</footer>
    </main>
</div>

<script>
(function(){
    var body = document.body, isMobile = function(){
     return window.innerWidth < 992; 
     };
    try {
     if (!isMobile() && localStorage.getItem('sbCollapsed') === '1') body.classList.add('sb-collapsed'); 
     } catch(e) {
     
     }
    document.getElementById('sbToggle').addEventListener('click', function(){
        if (isMobile()) { body.classList.toggle('sb-open'); }
        else {
            body.classList.toggle('sb-collapsed');
            try { localStorage.setItem('sbCollapsed', body.classList.contains('sb-collapsed') ? '1' : '0'); } catch(e) {}
        }
    });
    document.getElementById('sbOverlay').addEventListener('click', function(){ body.classList.remove('sb-open'); });
})();
</script>
<?= $extraScript ?>
</body>
</html>
<?php
}