<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$sy   = get_str('sy');
$term = get_str('term');

// Filter options (only this student's school years / terms)
$o = $conn->prepare("SELECT DISTINCT school_year, term FROM grades WHERE student_id = ? ORDER BY school_year DESC, term ASC");
$o->execute([$studentId]);
$periods = $o->fetchAll(PDO::FETCH_ASSOC);
$years = array_values(array_unique(array_column($periods, 'school_year')));
$terms = array_values(array_unique(array_column($periods, 'term')));

// Grades
$where  = "student_id = :sid";
$params = [':sid' => $studentId];
if ($sy !== '')   { $where .= " AND school_year = :sy";  $params[':sy']   = $sy; }
if ($term !== '') { $where .= " AND term = :term";       $params[':term'] = $term; }

$stmt = $conn->prepare(
    "SELECT subject_code, subject_name, instructor, school_year, term, grade
     FROM grades WHERE $where ORDER BY school_year DESC, term ASC, subject_name ASC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$graded = array_filter(array_column($rows, 'grade'), fn($v) => $v !== null && $v !== '');
$gwa    = $graded ? number_format(array_sum($graded) / count($graded), 2) : '—';

$printUrl = 'print_grades.php?' . http_build_query(['sy' => $sy, 'term' => $term]);

page_top('My Grades', 'grades.php');
?>

        <div class="welcome my-4 d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h2>My Grades</h2>
                <p class="mb-0"><?= count($rows) ?> subject<?= count($rows) === 1 ? '' : 's' ?> &middot; General average: <strong><?= e($gwa) ?></strong></p>
            </div>
            <a class="btn-white" href="<?= e($printUrl) ?>" target="_blank"><i class="bi bi-printer"></i>Print Grades</a>
        </div>

        <div class="glass section">
            <form method="GET" class="filters mb-4">
                <select name="sy" class="form-select">
                    <option value="">All school years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= e($y) ?>" <?= $y === $sy ? 'selected' : '' ?>><?= e($y) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="term" class="form-select">
                    <option value="">All terms</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= e($t) ?>" <?= $t === $term ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-white"><i class="bi bi-funnel"></i><span class="d-none d-sm-inline">Filter</span></button>
                <?php if ($sy !== '' || $term !== ''): ?>
                    <a class="btn-ghost" href="grades.php"><i class="bi bi-x-lg"></i><span class="d-none d-sm-inline">Clear</span></a>
                <?php endif; ?>
            </form>

            <?php if (!$rows): ?>
                <div class="empty"><i class="bi bi-clipboard-data"></i><p class="mt-2 mb-0">No grades found.</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-glass align-middle">
                    <thead>
                        <tr><th>Code</th><th>Subject</th><th>Instructor</th><th>School Year / Term</th><th>Grade</th><th>Remarks</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): $rm = remark($r['grade']); ?>
                        <tr>
                            <td><strong><?= e($r['subject_code']) ?></strong></td>
                            <td><?= e($r['subject_name']) ?></td>
                            <td><?= e($r['instructor'] ?: '—') ?></td>
                            <td><?= e($r['school_year']) ?> &middot; <?= e($r['term']) ?></td>
                            <td><strong><?= e(fmt_grade($r['grade'])) ?></strong></td>
                            <td><span class="pill <?= e($rm[1]) ?>"><?= e($rm[0]) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end"><strong>General Average</strong></td><td colspan="2"><strong><?= e($gwa) ?></strong></td></tr>
                    </tfoot>
                </table>
            </div>
            <p class="small mt-3 mb-0 opacity-75">Passing grade: <?= PASSING_GRADE ?>. INC means the grade is incomplete &mdash; you can file a <a class="link-light" href="compliance.php">compliance request</a>.</p>
            <?php endif; ?>
        </div>

<?php page_bottom(); ?>