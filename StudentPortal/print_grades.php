<?php
require __DIR__ . "/boot.php";

$sy   = get_str('sy');
$term = get_str('term');

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

$period = trim(($sy !== '' ? "S.Y. $sy" : 'All school years') . ' · ' . ($term !== '' ? $term : 'All terms'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Grade Report - <?= e($fullname) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="print.css" rel="stylesheet">
</head>
<body>

<div class="toolbar no-print">
    <button onclick="window.print()">Print</button>
    <button class="secondary" onclick="window.close()">Close</button>
</div>

<div class="sheet">
    <h1>Student Grade Report</h1>
    <p class="period"><?= e($period) ?></p>

    <table class="info">
        <tr><td>Name</td><td><strong><?= e($fullname) ?></strong></td></tr>
        <tr><td>Student ID</td><td><strong><?= e($studentId) ?></strong></td></tr>
        <tr><td>Date printed</td><td><?= e(date('F j, Y')) ?></td></tr>
    </table>

    <?php if (!$rows): ?>
        <p class="none">No grades found for the selected period.</p>
    <?php else: ?>
    <table class="grades">
        <thead>
            <tr><th>Code</th><th>Subject</th><th>Instructor</th><th>Term</th><th class="num">Grade</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): $rm = remark($r['grade']); ?>
            <tr>
                <td><?= e($r['subject_code']) ?></td>
                <td><?= e($r['subject_name']) ?></td>
                <td><?= e($r['instructor'] ?: '—') ?></td>
                <td><?= e($r['school_year']) ?> / <?= e($r['term']) ?></td>
                <td class="num"><?= e(fmt_grade($r['grade'])) ?></td>
                <td><?= e($rm[0]) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="num">General Average</td><td class="num"><?= e($gwa) ?></td><td></td></tr>
        </tfoot>
    </table>
    <p class="note">Passing grade: <?= PASSING_GRADE ?>. INC = incomplete.</p>
    <?php endif; ?>
</div>

<script>window.addEventListener('load', function(){ window.print(); });</script>
</body>
</html>