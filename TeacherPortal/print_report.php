<?php
require __DIR__ . "/boot.php";

$secId        = (int)($_GET['section'] ?? 0);
$sy           = get_str('sy');
$term         = get_str('term');
$studentParam = get_str('student');

$section = owned_section($conn, $secId, $teacherId);
if (!$section || !valid_sy($sy) || !in_array($term, TERMS, true)) {
    http_response_code(400);
    exit('Invalid report request.');
}

[$subjects, $rows] = build_report($conn, $secId, $sy, $term);

$one = null; $oneGrades = [];
if ($studentParam !== '') {
    foreach ($rows as $r) { if ((string)$r['student_id'] === $studentParam) { $one = $r; break; } }
    if (!$one) { http_response_code(404); exit('Student not found in this section.'); }

    $st = $conn->prepare(
        "SELECT subject_code, subject_name, instructor, grade FROM grades
         WHERE student_id = ? AND school_year = ? AND term = ? ORDER BY subject_code"
    );
    $st->execute([$studentParam, $sy, $term]);
    $oneGrades = $st->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $one ? 'Grade Report - ' . e($one['fullname']) : 'Section Grade Report - ' . e($section['name']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="print.css" rel="stylesheet">
</head>
<body>

<div class="toolbar no-print">
    <button onclick="window.print()">Print</button>
    <button class="secondary" onclick="window.close()">Close</button>
</div>

<div class="sheet">
<?php if ($one): ?>
    <h1>Student Grade Report</h1>
    <p class="period">S.Y. <?= e($sy) ?> &middot; <?= e($term) ?></p>

    <table class="info">
        <tr><td>Name</td><td><strong><?= e($one['fullname']) ?></strong></td></tr>
        <tr><td>Student ID</td><td><strong><?= e($one['student_id']) ?></strong></td></tr>
        <tr><td>Section</td><td><?= e($section['name']) ?></td></tr>
        <tr><td>Date printed</td><td><?= e(date('F j, Y')) ?></td></tr>
    </table>

    <?php if (!$oneGrades): ?>
        <p class="none">No grades found for this term.</p>
    <?php else: ?>
    <table class="grades">
        <thead><tr><th>Code</th><th>Subject</th><th>Instructor</th><th class="num">Grade</th><th>Remarks</th></tr></thead>
        <tbody>
            <?php foreach ($oneGrades as $g): $rm = remark($g['grade']); ?>
            <tr>
                <td><?= e($g['subject_code']) ?></td>
                <td><?= e($g['subject_name']) ?></td>
                <td><?= e($g['instructor'] ?: '—') ?></td>
                <td class="num"><?= e(fmt_grade($g['grade'])) ?></td>
                <td><?= e($rm[0]) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="num">General Average</td><td class="num"><?= $one['avg'] !== null ? e(number_format($one['avg'], 2)) : '—' ?></td><td><?= e($one['honor'] ?: '') ?></td></tr>
        </tfoot>
    </table>
    <?php if ($one['rank'] !== null): ?>
        <p class="note">Rank in section: <?= (int)$one['rank'] ?> of <?= count(array_filter($rows, fn($r) => $r['rank'] !== null)) ?>. Passing grade: <?= PASSING_GRADE ?>. INC = incomplete.</p>
    <?php endif; ?>
    <?php endif; ?>

<?php else: ?>
    <h1>Section Grade Report</h1>
    <p class="period"><?= e($section['name']) ?> &middot; S.Y. <?= e($sy) ?> &middot; <?= e($term) ?></p>

    <table class="info">
        <tr><td>Adviser / Teacher</td><td><strong><?= e($fullname) ?></strong></td></tr>
        <tr><td>Students</td><td><?= count($rows) ?></td></tr>
        <tr><td>Date printed</td><td><?= e(date('F j, Y')) ?></td></tr>
    </table>

    <?php if (!$rows || !$subjects): ?>
        <p class="none">No grades found for this section and term.</p>
    <?php else: ?>
    <table class="grades matrix">
        <thead>
            <tr>
                <th>#</th><th>Student</th>
                <?php foreach ($subjects as $s): ?><th class="num"><?= e($s['subject_code']) ?></th><?php endforeach; ?>
                <th class="num">Avg</th><th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r['rank'] !== null ? (int)$r['rank'] : '—' ?></td>
                <td><?= e($r['fullname']) ?></td>
                <?php foreach ($subjects as $s):
                    $has = array_key_exists($s['subject_code'], $r['grades']); ?>
                    <td class="num"><?= $has ? ($r['grades'][$s['subject_code']] === null ? 'INC' : e(fmt_grade($r['grades'][$s['subject_code']]))) : '' ?></td>
                <?php endforeach; ?>
                <td class="num"><?= $r['avg'] !== null ? e(number_format($r['avg'], 2)) : '—' ?></td>
                <td><?= e(row_remarks($r)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="note">
        <?php foreach ($subjects as $s): ?><?= e($s['subject_code']) ?> = <?= e($s['subject_name']) ?>;&nbsp;<?php endforeach; ?>
        Passing grade: <?= PASSING_GRADE ?>. INC = incomplete.
    </p>
    <?php endif; ?>
<?php endif; ?>
</div>

<script>window.addEventListener('load', function(){ window.print(); });</script>
</body>
</html>