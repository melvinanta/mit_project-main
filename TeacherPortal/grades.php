<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$sections = my_sections($conn, $teacherId);

function validate_meta(string $sy, string $term, string $code, string $name): array {
    $err = [];
    if (!valid_sy($sy))                              $err[] = "School year must look like 2026-2027.";
    if (!in_array($term, TERMS, true))               $err[] = "Choose a term.";
    if (!preg_match('/^[A-Z0-9._-]{2,20}$/', $code)) $err[] = "Subject code must be 2-20 characters (letters, numbers, dot, dash).";
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $err[] = "Enter the subject name.";
    return $err;
}

function section_members(PDO $conn, int $sectionId): array {
    $st = $conn->prepare(
        "SELECT u.user_id, u.fullname FROM section_students ss
         JOIN users u ON u.user_id = ss.student_id
         WHERE ss.section_id = ? ORDER BY u.fullname"
    );
    $st->execute([$sectionId]);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $out[$r['user_id']] = $r['fullname']; }
    return $out;
}


function save_grades(PDO $conn, array $members, array $raw, array $meta, string $teacherName): array {
    $saved = 0; $unchanged = 0; $skipped = 0; $errors = [];

    $find = $conn->prepare("SELECT id, grade FROM grades WHERE student_id = ? AND subject_code = ? AND school_year = ? AND term = ? LIMIT 1");
    $ins  = $conn->prepare("INSERT INTO grades (student_id, subject_code, subject_name, instructor, school_year, term, grade) VALUES (?,?,?,?,?,?,?)");
    $upd  = $conn->prepare("UPDATE grades SET grade = ?, subject_name = ?, instructor = ? WHERE id = ?");

    $conn->beginTransaction();
    try {
        foreach ($members as $sid => $name) {
            $val = isset($raw[$sid]) ? trim((string)$raw[$sid]) : '';
            if ($val === '') { $skipped++; continue; }

            if (strcasecmp($val, 'INC') === 0) {
                $grade = null;
            } elseif (preg_match('/^\d{1,3}(\.\d{1,2})?$/', $val) && (float)$val <= 100) {
                $grade = round((float)$val, 2);
            } else {
                $errors[] = "$name ($sid): \"$val\" is not a valid grade (use 0-100 or INC).";
                continue;
            }

            $find->execute([(string)$sid, $meta['code'], $meta['sy'], $meta['term']]);
            $row = $find->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $same = ($row['grade'] === null && $grade === null)
                     || ($row['grade'] !== null && $grade !== null && abs((float)$row['grade'] - $grade) < 0.005);
                if ($same) { $unchanged++; continue; }
                $upd->execute([$grade, $meta['name'], $teacherName, $row['id']]);
            } else {
                $ins->execute([(string)$sid, $meta['code'], $meta['name'], $teacherName, $meta['sy'], $meta['term'], $grade]);
            }
            $saved++;

            notify_student(
                $conn, (string)$sid,
                'Grade posted: ' . $meta['code'],
                'Your grade for ' . $meta['name'] . ' (' . $meta['term'] . ', S.Y. ' . $meta['sy'] . ') was ' . ($row ? 'updated' : 'posted') . '.'
            );
        }
        $conn->commit();
    } catch (Throwable $ex) {
        if ($conn->inTransaction()) $conn->rollBack();
        throw $ex;
    }
    return [$saved, $unchanged, $skipped, $errors];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secId = (int)($_POST['section'] ?? 0);
    $sy    = post_str('sy');
    $term  = post_str('term');
    $code  = strtoupper(post_str('code'));
    $sname = post_str('name');
    $back  = 'grades.php?' . http_build_query(['section' => $secId, 'sy' => $sy, 'term' => $term, 'code' => $code, 'name' => $sname]);

    if (!csrf_ok()) {
        flash_set('danger', 'Your session expired. Please try again.');
        header("Location: $back"); exit;
    }

    $errs = validate_meta($sy, $term, $code, $sname);
    if (!owned_section($conn, $secId, $teacherId)) $errs[] = "Choose one of your sections.";

    if ($errs) {
        flash_set('danger', implode("\n", $errs));
        header("Location: $back"); exit;
    }

    $members = section_members($conn, $secId);
    $meta    = ['sy' => $sy, 'term' => $term, 'code' => $code, 'name' => $sname];
    $action  = post_str('action');
    $raw     = [];
    $problems = [];

    if ($action === 'upload') {
        $f = $_FILES['csv'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            flash_set('danger', 'Choose a CSV file to upload.');
            header("Location: $back"); exit;
        }
        if ($f['size'] > 1024 * 1024) {
            flash_set('danger', 'The file is too large (1 MB maximum).');
            header("Location: $back"); exit;
        }
        if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'csv') {
            flash_set('danger', 'Only .csv files are accepted.');
            header("Location: $back"); exit;
        }

        $h = fopen($f['tmp_name'], 'r');
        $header = fgetcsv($h);
        if ($header) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]); }
        $cols = array_map(fn($c) => strtolower(trim((string)$c)), $header ?: []);
        $iId = array_search('student_id', $cols, true);
        $iGr = array_search('grade', $cols, true);
        if ($iId === false || $iGr === false) {
            fclose($h);
            flash_set('danger', 'The CSV needs a header row with "student_id" and "grade" columns. Download the template to start.');
            header("Location: $back"); exit;
        }
        $n = 0;
        while (($row = fgetcsv($h)) !== false && $n < 1000) {
            $n++;
            $id = trim((string)($row[$iId] ?? ''));
            if ($id === '') continue;
            if (!isset($members[$id])) { $problems[] = "Row $n: $id is not in this section."; continue; }
            $raw[$id] = (string)($row[$iGr] ?? '');
        }
        fclose($h);
    } else {
        $raw = (isset($_POST['g']) && is_array($_POST['g'])) ? $_POST['g'] : [];
    }

    try {
        [$saved, $unchanged, $skipped, $errors] = save_grades($conn, $members, $raw, $meta, $fullname);
    } catch (Throwable $ex) {
        flash_set('danger', 'Could not save the grades. Nothing was changed.');
        header("Location: $back"); exit;
    }

    $errors = array_merge($problems, $errors);
    $msg = "$saved grade" . ($saved === 1 ? '' : 's') . " saved";
    if ($unchanged) $msg .= ", $unchanged unchanged";
    if ($skipped)   $msg .= ", $skipped left blank";
    $msg .= ".";
    if ($errors) $msg .= "\n" . implode("\n", array_slice($errors, 0, 10)) . (count($errors) > 10 ? "\n…and " . (count($errors) - 10) . " more." : '');
    flash_set($errors ? 'warning' : 'success', $msg);
    header("Location: $back"); exit;
}


$secId = (int)($_GET['section'] ?? 0);
$sy    = get_str('sy') ?: default_school_year();
$term  = get_str('term') ?: TERMS[0];
$code  = strtoupper(get_str('code'));
$sname = get_str('name');

$section = owned_section($conn, $secId, $teacherId);
$ready   = $section && $code !== '' && $sname !== '' && !validate_meta($sy, $term, $code, $sname);

$rows = [];
if ($ready) {
    $st = $conn->prepare(
        "SELECT u.user_id, u.fullname, u.status, g.id AS gid, g.grade
         FROM section_students ss
         JOIN users u ON u.user_id = ss.student_id
         LEFT JOIN grades g ON g.student_id = u.user_id AND g.subject_code = ? AND g.school_year = ? AND g.term = ?
         WHERE ss.section_id = ? ORDER BY u.fullname"
    );
    $st->execute([$code, $sy, $term, $secId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

   
    if (isset($_GET['template'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="grades-' . preg_replace('/[^A-Za-z0-9_-]/', '', $code) . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['student_id', 'fullname', 'grade']);
        foreach ($rows as $r) {
            $val = $r['gid'] === null ? '' : ($r['grade'] === null ? 'INC' : (string)(float)$r['grade']);
            fputcsv($out, [csv_safe($r['user_id']), csv_safe($r['fullname']), $val]);
        }
        fclose($out);
        exit;
    }
}

$meta = ['section' => $secId, 'sy' => $sy, 'term' => $term, 'code' => $code, 'name' => $sname];

page_top('Manage Grades', 'grades.php');
?>

        <div class="welcome my-4">
            <h2>Manage Grades</h2>
            <p>Pick a section and subject, then type the grades or upload a CSV.</p>
        </div>

        <?php if (!$sections): ?>
            <div class="glass section">
                <div class="empty"><i class="bi bi-diagram-3"></i>
                    <p class="mt-2 mb-2">Create a section and add students before posting grades.</p>
                    <a class="btn-white d-inline-flex" href="sections.php"><i class="bi bi-plus-lg"></i>Create a section</a>
                </div>
            </div>
        <?php else: ?>

        <div class="glass section glass-form mb-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-3">
                    <label for="section">Section</label>
                    <select id="section" name="section" class="form-select" required>
                        <option value="">Choose…</option>
                        <?php foreach ($sections as $s): ?>
                            <!-- <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secId ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['school_year']) ?>)</option> -->
                             <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secId ? 'selected' : '' ?>><?= e(trim((($s['grade_level'] ?? '') !== '' ? 'Grade ' . $s['grade_level'] . ' ' : '') . $s['name'])) ?> (<?= e($s['school_year']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-xl-2">
                    <label for="sy">School year</label>
                    <input id="sy" name="sy" class="form-control" pattern="\d{4}-\d{4}" value="<?= e($sy) ?>" required>
                </div>
                <div class="col-6 col-xl-2">
                    <label for="term">Term</label>
                    <select id="term" name="term" class="form-select">
                        <?php foreach (TERMS as $t): ?>
                            <option value="<?= e($t) ?>" <?= $t === $term ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-5 col-xl-2">
                    <label for="code">Subject code</label>
                    <input id="code" name="code" class="form-control" maxlength="20" placeholder="MATH101" value="<?= e($code) ?>" required>
                </div>
                <div class="col-7 col-xl-3">
                    <label for="name">Subject name</label>
                    <input id="name" name="name" class="form-control" maxlength="100" placeholder="Mathematics" value="<?= e($sname) ?>" required>
                </div>
                <div class="col-12">
                    <button class="btn-white"><i class="bi bi-table"></i>Load students</button>
                </div>
            </form>
        </div>

        <?php if ($ready): ?>
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="glass section h-100">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h5 class="m-0"><?= e($code) ?> &middot; <?= e($sname) ?></h5>
                            <div class="small opacity-75"><?= e($section['name']) ?> &middot; S.Y. <?= e($sy) ?> &middot; <?= e($term) ?></div>
                        </div>
                    </div>

                    <?php if (!$rows): ?>
                        <div class="empty"><i class="bi bi-people"></i><p class="mt-2 mb-0">This section has no students yet.</p></div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="save">
                        <?php foreach ($meta as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>

                        <div class="table-responsive">
                            <table class="table table-glass align-middle">
                                <thead><tr><th>Student</th><th>Student ID</th><th style="width:150px">Grade</th><th>Remarks</th></tr></thead>
                                <tbody>
                                    <?php foreach ($rows as $r):
                                        $val = $r['gid'] === null ? '' : ($r['grade'] === null ? 'INC' : rtrim(rtrim(number_format((float)$r['grade'], 2, '.', ''), '0'), '.'));
                                        $rm  = $r['gid'] === null ? null : remark($r['grade']);
                                    ?>
                                    <tr>
                                        <td><?= e($r['fullname']) ?><?= $r['status'] === 'inactive' ? ' <span class="pill bad">Inactive</span>' : '' ?></td>
                                        <td><span class="mono"><?= e($r['user_id']) ?></span></td>
                                        <td><input class="grade-input" name="g[<?= e($r['user_id']) ?>]" value="<?= e($val) ?>" maxlength="6" placeholder="—" autocomplete="off"></td>
                                        <td><?= $rm ? '<span class="pill ' . e($rm[1]) . '">' . e($rm[0]) . '</span>' : '<span class="opacity-75">Not graded</span>' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="small opacity-75">Enter 0&ndash;100, or <strong>INC</strong> for incomplete. Leave blank to skip a student. Students are notified when their grade is posted or changed.</p>
                        <button class="btn-white"><i class="bi bi-check2-circle"></i>Save Grades</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="glass section h-100 glass-form">
                    <h5>Upload CSV</h5>
                    <p class="small opacity-75">Download the template (your students are pre-filled), type the grades in Excel, then upload it here.</p>
                    <a class="btn-ghost mb-3" href="grades.php?<?= e(http_build_query($meta + ['template' => 1])) ?>"><i class="bi bi-download"></i>Download template</a>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="upload">
                        <?php foreach ($meta as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                        <label for="csv">CSV file</label>
                        <input id="csv" type="file" name="csv" accept=".csv,text/csv" class="form-control mb-3" required>
                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-upload"></i>Upload &amp; Save</button>
                    </form>
                    <p class="small opacity-75 mt-3 mb-0">Columns: <span class="mono">student_id, fullname, grade</span>. Only students in this section are accepted.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>

<?php page_bottom(); ?>