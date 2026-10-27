<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$sections = my_sections($conn, $teacherId);


$sy   = get_str('sy');
$term = get_str('term');
if ($sy !== '' && !valid_sy($sy)) $sy = '';
if ($term !== '' && !in_array($term, TERMS, true)) $term = '';

$p = $conn->prepare(
    "SELECT DISTINCT g.school_year, g.term
     FROM grades g
     JOIN section_students ss ON ss.student_id = g.student_id
     JOIN sections s ON s.id = ss.section_id
     WHERE s.teacher_id = ? ORDER BY g.school_year DESC, g.term"
);
$p->execute([$teacherId]);
$periods = $p->fetchAll(PDO::FETCH_ASSOC);
$years   = array_values(array_unique(array_column($periods, 'school_year')));
$terms   = array_values(array_unique(array_column($periods, 'term')));


$boards = [];
$honorCount = 0;
foreach ($sections as $s) {
    $ranked = array_values(array_filter(section_ranking($conn, (int)$s['id'], $sy, $term), fn($r) => $r['rank'] !== null));
    foreach ($ranked as $r) { if ($r['honor']) $honorCount++; }
    $boards[] = ['section' => $s, 'ranked' => $ranked];
}


$rq = $conn->prepare(
    "SELECT r.student_id, u.fullname, r.subject, r.type, r.created_at
     FROM compliance_requests r
     JOIN section_students ss ON ss.student_id = r.student_id
     JOIN sections s ON s.id = ss.section_id
     JOIN users u ON u.user_id = r.student_id
     WHERE s.teacher_id = ? AND r.status = 'pending'
     ORDER BY r.created_at DESC, r.id DESC LIMIT 5"
);
$rq->execute([$teacherId]);
$pendingList = $rq->fetchAll(PDO::FETCH_ASSOC);

$totalStudents = array_sum(array_column($sections, 'students'));

$stats = [
    ['Sections',         count($sections), 'bi-diagram-3'],
    ['Students',         $totalStudents,   'bi-people'],
    ['Pending Requests', $pendingCount,    'bi-hourglass-split'],
    ['Honor Students',   $honorCount,      'bi-trophy'],
];

page_top('Teacher Dashboard', 'teacher_dashboard.php');
?>

        <div class="welcome my-4">
            <h2>Welcome, <?= e($fullname) ?></h2>
            <p>Here's how your sections are doing.</p>
        </div>

        <div class="row g-3 mb-4">
            <?php foreach ($stats as $st): ?>
            <div class="col-6 col-xl-3">
                <div class="glass stat-card">
                    <i class="bi <?= e($st[2]) ?>"></i>
                    <div class="num"><?= e($st[1]) ?></div>
                    <div class="lbl"><?= e($st[0]) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="glass section h-100">
                    <h5>Quick Actions</h5>
                    <div class="d-grid gap-3">
                        <a class="quick" href="students.php"><i class="bi bi-person-plus fs-5"></i>Add Student</a>
                        <a class="quick" href="grades.php"><i class="bi bi-upload fs-5"></i>Upload Grades</a>
                        <a class="quick" href="requests.php"><i class="bi bi-file-earmark-check fs-5"></i>Review Requests</a>
                        <a class="quick" href="reports.php"><i class="bi bi-bar-chart-line fs-5"></i>Generate Report</a>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Pending Requests</h5>
                        <a class="link-light fw-semibold text-decoration-none" href="requests.php">View all</a>
                    </div>
                    <?php if (!$pendingList): ?>
                        <div class="empty"><i class="bi bi-inbox"></i><p class="mt-2 mb-0">No pending requests.</p></div>
                    <?php else: ?>
                        <?php foreach ($pendingList as $r): ?>
                        <div class="note unread">
                            <i class="bi bi-file-earmark-check"></i>
                            <div class="flex-grow-1">
                                <strong><?= e($r['fullname']) ?></strong> &middot; <?= e($r['subject']) ?>
                                <div><?= e($r['type']) ?></div>
                                <small><?= e(date('M j, Y g:i A', strtotime($r['created_at']))) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="glass section">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h5 class="m-0"><i class="bi bi-trophy-fill me-2"></i>Section Achievers</h5>
                <form method="GET" class="filters">
                    <select name="sy" class="form-select" style="max-width:170px">
                        <option value="">All school years</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= e($y) ?>" <?= $y === $sy ? 'selected' : '' ?>><?= e($y) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="term" class="form-select" style="max-width:170px">
                        <option value="">All terms</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= e($t) ?>" <?= $t === $term ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn-white"><i class="bi bi-funnel"></i>Filter</button>
                </form>
            </div>

            <?php if (!$sections): ?>
                <div class="empty"><i class="bi bi-diagram-3"></i>
                    <p class="mt-2 mb-2">You have no sections yet.</p>
                    <a class="btn-white d-inline-flex" href="sections.php"><i class="bi bi-plus-lg"></i>Create a section</a>
                </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($boards as $b):
                    $s = $b['section'];
                    $link = 'reports.php?' . http_build_query([
                        'section' => $s['id'],
                        'sy'      => $sy !== '' ? $sy : $s['school_year'],
                        'term'    => $term !== '' ? $term : TERMS[0],
                    ]);
                ?>
                <div class="col-lg-6">
                    <div class="board">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <!-- <strong class="fs-5"><?= e($s['name']) ?></strong>
                                  -->
                                <strong class="fs-5"><?= e(trim((($s['grade_level'] ?? '') !== '' ? 'Grade ' . $s['grade_level'] . ' ' : '') . $s['name'])) ?></strong>
                                <div class="small opacity-75">S.Y. <?= e($s['school_year']) ?> &middot; <?= (int)$s['students'] ?> student<?= (int)$s['students'] === 1 ? '' : 's' ?></div>
                            </div>
                            <a class="btn-ghost btn-sm-x" href="<?= e($link) ?>">Full ranking</a>
                        </div>

                        <?php if (!$b['ranked']): ?>
                            <div class="empty py-3"><p class="mb-0">No grades posted for this period.</p></div>
                        <?php else: ?>
                        <div class="achiever-list">
                            <?php foreach (array_slice($b['ranked'], 0, 3) as $r): ?>
                            <div class="achiever">
                                <span class="rank r<?= (int)$r['rank'] <= 3 ? (int)$r['rank'] : 0 ?>"><?= (int)$r['rank'] ?></span>
                                <div class="flex-grow-1">
                                    <strong><?= e($r['fullname']) ?></strong>
                                    <div class="small opacity-75"><?= e($r['student_id']) ?></div>
                                </div>
                                <div class="text-end">
                                    <strong><?= e(number_format($r['avg'], 2)) ?></strong>
                                    <?php if ($r['honor']): ?><div><span class="pill gold"><?= e($r['honor']) ?></span></div><?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="small mt-3 mb-0 opacity-75">Ranked by general average. Honors require no failed or INC grades: <?= e(implode(', ', array_map(fn($h) => $h[1] . ' ' . $h[0] . '+', HONOR_LEVELS))) ?>.</p>
            <?php endif; ?>
        </div>

<?php page_bottom(); ?>