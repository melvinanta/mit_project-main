<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

// Grade summary
$s = $conn->prepare("SELECT COUNT(*) AS c, AVG(grade) AS g FROM grades WHERE student_id = ?");
$s->execute([$studentId]);
$sum      = $s->fetch(PDO::FETCH_ASSOC);
$subjects = (int)$sum['c'];
$gwa      = $sum['g'] !== null ? number_format((float)$sum['g'], 2) : '—';

// Pending compliance requests
$p = $conn->prepare("SELECT COUNT(*) FROM compliance_requests WHERE student_id = ? AND status = 'pending'");
$p->execute([$studentId]);
$pending = (int)$p->fetchColumn();

// Latest grades
$g = $conn->prepare(
    "SELECT subject_code, subject_name, school_year, term, grade
     FROM grades WHERE student_id = ? ORDER BY created_at DESC, id DESC LIMIT 5"
);
$g->execute([$studentId]);
$recentGrades = $g->fetchAll(PDO::FETCH_ASSOC);

// Latest notifications
$n = $conn->prepare(
    "SELECT title, message, is_read, created_at
     FROM notifications WHERE student_id = ? ORDER BY created_at DESC, id DESC LIMIT 4"
);
$n->execute([$studentId]);
$recentNotes = $n->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    ['Subjects',             $subjects,    'bi-journal-bookmark'],
    ['General Average',      $gwa,         'bi-graph-up'],
    ['Pending Compliance',   $pending,     'bi-hourglass-split'],
    ['Unread Notifications', $unreadCount, 'bi-bell'],
];

page_top('Student Dashboard', 'student_dashboard.php');
?>

        <div class="welcome my-4">
            <h2>Welcome, <?= e($fullname) ?></h2>
            <p>Student ID: <?= e($studentId) ?></p>
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
                        <a class="quick" href="grades.php"><i class="bi bi-clipboard-data fs-5"></i>View Grades</a>
                        <a class="quick" href="print_grades.php" target="_blank"><i class="bi bi-printer fs-5"></i>Print Grades</a>
                        <a class="quick" href="compliance.php"><i class="bi bi-file-earmark-check fs-5"></i>Request Compliance</a>
                        <a class="quick" href="notifications.php"><i class="bi bi-bell fs-5"></i>Notifications</a>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Latest Grades</h5>
                        <a class="link-light fw-semibold text-decoration-none" href="grades.php">View all</a>
                    </div>
                    <?php if (!$recentGrades): ?>
                        <div class="empty"><i class="bi bi-clipboard-data"></i><p class="mt-2 mb-0">No grades posted yet.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-glass align-middle">
                            <thead><tr><th>Subject</th><th>Term</th><th>Grade</th><th>Remarks</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentGrades as $r): $rm = remark($r['grade']); ?>
                                <tr>
                                    <td><strong><?= e($r['subject_code']) ?></strong> &middot; <?= e($r['subject_name']) ?></td>
                                    <td><?= e($r['school_year']) ?> &middot; <?= e($r['term']) ?></td>
                                    <td><?= e(fmt_grade($r['grade'])) ?></td>
                                    <td><span class="pill <?= e($rm[1]) ?>"><?= e($rm[0]) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="glass section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0">Recent Notifications</h5>
                <a class="link-light fw-semibold text-decoration-none" href="notifications.php">View all</a>
            </div>
            <?php if (!$recentNotes): ?>
                <div class="empty"><i class="bi bi-bell"></i><p class="mt-2 mb-0">You have no notifications.</p></div>
            <?php else: ?>
                <?php foreach ($recentNotes as $nt): ?>
                <div class="note <?= $nt['is_read'] ? '' : 'unread' ?>">
                    <i class="bi bi-bell-fill"></i>
                    <div>
                        <strong><?= e($nt['title']) ?></strong>
                        <div><?= e($nt['message']) ?></div>
                        <small><?= e(date('M j, Y g:i A', strtotime($nt['created_at']))) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

<?php page_bottom(); ?>