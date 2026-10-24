<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$sections = my_sections($conn, $teacherId);

$secId = (int)($_GET['section'] ?? 0);
$sy    = get_str('sy') ?: default_school_year();
$term  = get_str('term') ?: TERMS[0];

$section = owned_section($conn, $secId, $teacherId);
$ready   = $section && valid_sy($sy) && in_array($term, TERMS, true);

$subjects = []; $rows = [];
if ($ready) {
    [$subjects, $rows] = build_report($conn, $secId, $sy, $term);


    if (get_str('export') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report-' . preg_replace('/[^A-Za-z0-9_-]/', '', $sy . '-' . $term) . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_merge(['Rank', 'Student ID', 'Name'], array_map(fn($s) => csv_safe($s['subject_code']), $subjects), ['General Average', 'Remarks']));
        foreach ($rows as $r) {
            $line = [$r['rank'] ?? '', csv_safe($r['student_id']), csv_safe($r['fullname'])];
            foreach ($subjects as $s) {
                $line[] = array_key_exists($s['subject_code'], $r['grades']) ? ($r['grades'][$s['subject_code']] === null ? 'INC' : fmt_grade($r['grades'][$s['subject_code']])) : '';
            }
            $line[] = $r['avg'] !== null ? number_format($r['avg'], 2) : '';
            $line[] = row_remarks($r);
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }
}

$params = ['section' => $secId, 'sy' => $sy, 'term' => $term];

page_top('Reports', 'reports.php');
?>

        <div class="welcome my-4">
            <h2>Reports</h2>
            <p>Section grade report with ranking. Print it or export to Excel.</p>
        </div>

        <?php if (!$sections): ?>
            <div class="glass section">
                <div class="empty"><i class="bi bi-diagram-3"></i>
                    <p class="mt-2 mb-2">Create a section first.</p>
                    <a class="btn-white d-inline-flex" href="sections.php"><i class="bi bi-plus-lg"></i>Create a section</a>
                </div>
            </div>
        <?php else: ?>

        <div class="glass section glass-form mb-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-4">
                    <label for="section">Section</label>
                    <select id="section" name="section" class="form-select" required>
                        <option value="">Choose…</option>
                        <?php foreach ($sections as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secId ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['school_year']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-xl-2">
                    <label for="sy">School year</label>
                    <input id="sy" name="sy" class="form-control" pattern="\d{4}-\d{4}" value="<?= e($sy) ?>" required>
                </div>
                <div class="col-6 col-xl-3">
                    <label for="term">Term</label>
                    <select id="term" name="term" class="form-select">
                        <?php foreach (TERMS as $t): ?>
                            <option value="<?= e($t) ?>" <?= $t === $term ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-xl-3">
                    <button class="btn-white w-100 justify-content-center"><i class="bi bi-bar-chart-line"></i>Generate</button>
                </div>
            </form>
        </div>

        <?php if ($ready): ?>
        <div class="glass section">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h5 class="m-0"><?= e($section['name']) ?></h5>
                    <div class="small opacity-75">S.Y. <?= e($sy) ?> &middot; <?= e($term) ?> &middot; <?= count($rows) ?> student<?= count($rows) === 1 ? '' : 's' ?></div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn-white" target="_blank" href="print_report.php?<?= e(http_build_query($params)) ?>"><i class="bi bi-printer"></i>Print</a>
                    <a class="btn-ghost" href="reports.php?<?= e(http_build_query($params + ['export' => 'csv'])) ?>"><i class="bi bi-file-earmark-spreadsheet"></i>Export CSV</a>
                </div>
            </div>

            <?php if (!$rows || !$subjects): ?>
                <div class="empty"><i class="bi bi-clipboard-data"></i><p class="mt-2 mb-0">No grades posted for this section and term yet.</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-glass align-middle">
                    <thead>
                        <tr>
                            <th>#</th><th>Student</th>
                            <?php foreach ($subjects as $s): ?><th class="text-center" title="<?= e($s['subject_name']) ?>"><?= e($s['subject_code']) ?></th><?php endforeach; ?>
                            <th class="text-center">Average</th><th>Remarks</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $r['rank'] !== null ? (int)$r['rank'] : '—' ?></td>
                            <td><?= e($r['fullname']) ?><div class="small opacity-75 mono"><?= e($r['student_id']) ?></div></td>
                            <?php foreach ($subjects as $s):
                                $has = array_key_exists($s['subject_code'], $r['grades']);
                                $gv  = $has ? $r['grades'][$s['subject_code']] : null;
                                $low = $has && $gv !== null && (float)$gv < PASSING_GRADE;
                            ?>
                                <td class="text-center <?= $low ? 'low' : '' ?>"><?= $has ? ($gv === null ? 'INC' : e(fmt_grade($gv))) : '' ?></td>
                            <?php endforeach; ?>
                            <td class="text-center"><strong><?= $r['avg'] !== null ? e(number_format($r['avg'], 2)) : '—' ?></strong></td>
                            <td><?= $r['honor'] ? '<span class="pill gold">' . e($r['honor']) . '</span>' : e(row_remarks($r)) ?></td>
                            <td class="text-end">
                                <a class="btn-ghost btn-sm-x" target="_blank" title="Print this student's grades"
                                   href="print_report.php?<?= e(http_build_query($params + ['student' => $r['student_id']])) ?>"><i class="bi bi-printer"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small mt-3 mb-0 opacity-75">Passing grade: <?= PASSING_GRADE ?>. Grades below it are highlighted. Inactive students are left out.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>

<?php page_bottom(); ?>