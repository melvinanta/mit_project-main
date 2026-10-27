<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/report_lib.php";

[$type, $level, $secId, $sy, $term] = report_params();
$report = build_admin_report($conn, $type, $level, $secId, $sy, $term);
if ($report['error']) { http_response_code(400); exit(e($report['error'])); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($report['title']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="print.css" rel="stylesheet">
</head>
<body>

<div class="toolbar no-print">
    <button onclick="window.print()">Print</button>
    <button class="secondary" onclick="window.close()">Close</button>
</div>

<div class="sheet">
    <h1><?= e($report['title']) ?></h1>
    <p class="period"><?= e(implode('  ·  ', $report['meta'])) ?></p>
    <table class="info">
        <tr><td>Prepared by</td><td><strong><?= e($fullname) ?></strong></td></tr>
        <tr><td>Date printed</td><td><?= e(date('F j, Y')) ?></td></tr>
    </table>

    <?php foreach ($report['tables'] as $t): ?>
        <h3 style="margin:18px 0 6px"><?= e($t['caption']) ?></h3>
        <?php if (!$t['rows']): ?>
            <p class="none">Nothing to show for this selection.</p>
        <?php else: ?>
        <table class="grades matrix">
            <thead><tr><?php foreach ($t['head'] as $i => $h): ?><th class="<?= in_array($i, $t['num'], true) ? 'num' : '' ?>"><?= e($h) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
                <?php foreach ($t['rows'] as $r): ?>
                <tr><?php foreach ($r as $i => $c): ?><td class="<?= in_array($i, $t['num'], true) ? 'num' : '' ?>"><?= e($c) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!empty($t['note'])): ?><p class="note"><?= e($t['note']) ?></p><?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    <p class="note">Passing grade: <?= (int)PASSING_GRADE ?>. INC = incomplete (not counted in pass rate).</p>
</div>

<script>window.addEventListener('load', function(){ window.print(); });</script>
</body>
</html>