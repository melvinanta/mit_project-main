<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/report_lib.php";

[$type, $level, $secId, $sy, $term] = report_params();
$submitted = $type !== '';
$report = $submitted ? build_admin_report($conn, $type, $level, $secId, $sy, $term) : null;


if ($report && !$report['error'] && get_str('export') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report-' . $type . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', ($sy ?: 'all') . '-' . ($term ?: 'all')) . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, [csv_safe($report['title'])]);
    foreach ($report['meta'] as $m) fputcsv($o, [csv_safe($m)]);
    foreach ($report['tables'] as $t) {
        fputcsv($o, []);
        fputcsv($o, [csv_safe($t['caption'])]);
        fputcsv($o, array_map('csv_safe', $t['head']));
        foreach ($t['rows'] as $r) fputcsv($o, array_map('csv_safe', $r));
    }
    fclose($o);
    exit;
}

require __DIR__ . "/layout.php";
$sections = all_sections($conn);
$params   = array_filter(['type' => $type, 'level' => $level, 'section' => $secId ?: '', 'sy' => $sy, 'term' => $term], fn($v) => $v !== '');

page_top('Reports', 'reports.php');
?>

        <div class="welcome my-4">
            <h2>Reports</h2>
            <p>School overview, grade-level and section reports. Print them or export to Excel.</p>
        </div>

        <div class="glass section glass-form mb-3">
            <form method="GET" class="row g-3 align-items-end" id="rf">
                <div class="col-md-6 col-xl-3">
                    <label for="type">Report</label>
                    <select id="type" name="type" class="form-select" required>
                        <option value="overview" <?= $type === 'overview' ? 'selected' : '' ?>>School overview</option>
                        <option value="level"    <?= $type === 'level' ? 'selected' : '' ?>>Grade level</option>
                        <option value="section"  <?= $type === 'section' ? 'selected' : '' ?>>Section (with subject grades)</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3" data-for="level">
                    <label for="level">Grade level</label>
                    <select id="level" name="level" class="form-select">
                        <option value="">Choose…</option>
                        <?php foreach (GRADE_LEVELS as $g): ?><option <?= $g === $level ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3" data-for="section">
                    <label for="section">Grade &amp; Section</label>
                    <select id="section" name="section" class="form-select">
                        <option value="">Choose…</option>
                        <?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secId ? 'selected' : '' ?>><?= e(sec_title($s)) ?> (<?= e($s['school_year']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-xl-2">
                    <label for="sy">School year</label>
                    <input id="sy" name="sy" class="form-control" pattern="\d{4}-\d{4}" placeholder="All" value="<?= e($sy ?: ($submitted ? '' : default_school_year())) ?>">
                </div>
                <div class="col-6 col-xl-2">
                    <label for="term">Term</label>
                    <select id="term" name="term" class="form-select">
                        <option value="" data-all>All terms</option>
                        <?php foreach (TERMS as $t): ?><option <?= $t === $term ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-xl-2">
                    <button class="btn-white w-100 justify-content-center"><i class="bi bi-bar-chart-line"></i>Generate</button>
                </div>
            </form>
        </div>

        <?php if ($report && $report['error']): ?>
            <div class="alert alert-warning glass-alert"><?= e($report['error']) ?></div>
        <?php elseif ($report): ?>
        <div class="glass section">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h5 class="m-0"><?= e($report['title']) ?></h5>
                    <?php foreach ($report['meta'] as $m): ?><div class="small opacity-75"><?= e($m) ?></div><?php endforeach; ?>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn-white" target="_blank" href="print_report.php?<?= e(http_build_query($params)) ?>"><i class="bi bi-printer"></i>Print</a>
                    <a class="btn-ghost" href="reports.php?<?= e(http_build_query($params + ['export' => 'csv'])) ?>"><i class="bi bi-file-earmark-spreadsheet"></i>Export CSV</a>
                </div>
            </div>

            <?php foreach ($report['tables'] as $t): ?>
                <h6 class="mt-4 mb-2"><?= e($t['caption']) ?></h6>
                <?php if (!$t['rows']): ?>
                    <div class="empty py-3"><p class="mb-0">Nothing to show for this selection.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-glass align-middle">
                        <thead><tr><?php foreach ($t['head'] as $i => $h): ?><th class="<?= in_array($i, $t['num'], true) ? 'num' : '' ?>"><?= e($h) ?></th><?php endforeach; ?></tr></thead>
                        <tbody>
                            <?php foreach ($t['rows'] as $r): ?>
                            <tr><?php foreach ($r as $i => $c): ?><td class="<?= in_array($i, $t['num'], true) ? 'num' : '' ?>"><?= e($c) ?></td><?php endforeach; ?></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($t['note'])): ?><p class="small opacity-75 mb-0"><?= e($t['note']) ?></p><?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <p class="small mt-3 mb-0 opacity-75">Passing grade: <?= (int)PASSING_GRADE ?>. Pass rate counts graded subjects only (INC is excluded). Inactive students are left out.</p>
        </div>
        <?php endif; ?>

<?php
ob_start(); ?>
<script>
(function(){
    var type = document.getElementById('type'), term = document.getElementById('term');
    function sync(){
        document.querySelectorAll('[data-for]').forEach(function(el){
            el.style.display = el.getAttribute('data-for') === type.value ? '' : 'none';
        });
        
        var all = term.querySelector('[data-all]');
        all.disabled = type.value === 'section';
        if (all.disabled && term.value === '') term.selectedIndex = 1;
    }
    type.addEventListener('change', sync); sync();
})();
</script>
<?php page_bottom(ob_get_clean()); ?>