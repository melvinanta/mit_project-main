<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$sections = my_sections($conn, $teacherId);

// ---- Add a student (creates the login) ----
// "Grade 12 Sharks" (grade level is optional)
function sec_title(array $s): string {
    $gl = trim((string)($s['grade_level'] ?? ''));
    if ($gl !== '' && stripos($gl, 'grade') !== 0) $gl = 'Grade ' . $gl;
    return trim($gl . ' ' . $s['name']);
}

/* ---------- Credentials list of the last bulk upload (kept in the session until downloaded or dismissed) ---------- */
if (isset($_GET['creds'])) {
    $list = $_SESSION['bulk_cred'] ?? [];
    if ($_GET['creds'] === 'csv' && $list) {
        unset($_SESSION['bulk_cred']);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="new-student-logins.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['student_id', 'fullname', 'password', 'section']);
        foreach ($list as $c) fputcsv($out, [csv_safe($c['id']), csv_safe($c['name']), csv_safe($c['password']), csv_safe($c['section'])]);
        fclose($out);
        exit;
    }
    unset($_SESSION['bulk_cred']);
    header("Location: students.php");
    exit;
}

/* ---------- Blank CSV template ---------- */
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students-template.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['student_id', 'fullname', 'password']);
    fclose($out);
    exit;
}

/* ---------- Bulk upload ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_str('action') === 'upload_students') {
    $secId = (int)($_POST['section_id'] ?? 0);
    $back  = "students.php?section=" . $secId;

    if (!csrf_ok()) { flash_set('danger', 'Your session expired. Please try again.'); header("Location: students.php"); exit; }
    $sec = owned_section($conn, $secId, $teacherId);
    if (!$sec) { flash_set('danger', 'Choose one of your sections.'); header("Location: students.php"); exit; }

    $f = $_FILES['csv'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) { flash_set('danger', 'Choose a CSV file to upload.'); header("Location: $back"); exit; }
    if ($f['size'] > 1024 * 1024) { flash_set('danger', 'The file is too large (1 MB maximum).'); header("Location: $back"); exit; }
    if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'csv') { flash_set('danger', 'Only .csv files are accepted.'); header("Location: $back"); exit; }

    $h = fopen($f['tmp_name'], 'r');
    $first = (string)fgets($h);
    rewind($h);
    $delim = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';   // Excel in some regions saves with ;
    $header = fgetcsv($h, 0, $delim);
    if ($header) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]); }
    $cols  = array_map(fn($c) => strtolower(trim((string)$c)), $header ?: []);
    $iId   = array_search('student_id', $cols, true);
    $iName = array_search('fullname', $cols, true);
    $iPw   = array_search('password', $cols, true);
    if ($iId === false || $iName === false) {
        fclose($h);
        flash_set('danger', 'The CSV needs a header row with "student_id" and "fullname" columns (and optionally "password"). Download the template to start.');
        header("Location: $back"); exit;
    }

    @set_time_limit(180);   // hashing passwords takes a moment per student
    $chk   = $conn->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
    $has   = $conn->prepare("SELECT COUNT(*) FROM section_students WHERE student_id = ?");
    $insU  = $conn->prepare("INSERT INTO users (user_id, fullname, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
    $insS  = $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)");

    $created = []; $added = 0; $errors = []; $line = 1; $seen = 0;
    while (($row = fgetcsv($h, 0, $delim)) !== false) {
        $line++;
        if (count($row) === 1 && trim((string)$row[0]) === '') continue;       // blank line
        if (++$seen > 500) { $errors[] = "Only the first 500 rows were processed."; break; }

        $sid  = trim((string)($row[$iId] ?? ''));
        $name = trim((string)($row[$iName] ?? ''));
        $pw   = $iPw !== false ? (string)($row[$iPw] ?? '') : '';
        $pw   = trim($pw);

        if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $sid))      { $errors[] = "Row $line: invalid student ID \"$sid\"."; continue; }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150)     { $errors[] = "Row $line ($sid): full name is missing or too long."; continue; }
        if ($pw !== '' && (strlen($pw) < 8 || strlen($pw) > 72)) { $errors[] = "Row $line ($sid): password must be 8 to 72 characters (or leave it blank)."; continue; }

        try {
            $chk->execute([$sid]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $has->execute([$sid]);
                if ($existing['role'] !== 'student' || (int)$has->fetchColumn() > 0) { $errors[] = "Row $line ($sid): that ID is already in use."; continue; }
                $insS->execute([$secId, $sid]);
                $added++;
            } else {
                $plain = $pw !== '' ? $pw : gen_password();
                $conn->beginTransaction();
                $insU->execute([$sid, $name, password_hash($plain, PASSWORD_DEFAULT)]);
                $insS->execute([$secId, $sid]);
                $conn->commit();
                $created[] = ['id' => $sid, 'name' => $name, 'password' => $plain, 'section' => sec_title($sec)];
            }
        } catch (PDOException $ex) {
            if ($conn->inTransaction()) $conn->rollBack();
            $errors[] = "Row $line ($sid): could not be saved.";
        }
    }
    fclose($h);

    if ($created) $_SESSION['bulk_cred'] = $created;

    $msg = count($created) . " account" . (count($created) === 1 ? '' : 's') . " created";
    if ($added)  $msg .= ", $added existing student" . ($added === 1 ? '' : 's') . " added to the section";
    if ($errors) $msg .= ", " . count($errors) . " row" . (count($errors) === 1 ? '' : 's') . " skipped";
    $msg .= ".";
    if ($errors) $msg .= "\n" . implode("\n", array_slice($errors, 0, 10)) . (count($errors) > 10 ? "\n…and " . (count($errors) - 10) . " more." : '');
    flash_set($errors ? 'warning' : 'success', $msg);
    header("Location: $back"); exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash_set('danger', 'Your session expired. Please try again.');
        header("Location: students.php");
        exit;
    }

    $sid   = post_str('student_id');
    $name  = post_str('fullname');
    $secId = (int)($_POST['section_id'] ?? 0);
    $pw    = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    $err = [];
    if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $sid)) $err[] = "Student ID must be 3-30 characters: letters, numbers, dot, dash or underscore.";
    if (mb_strlen($name) < 2 || mb_strlen($name) > 150) $err[] = "Enter the student's full name.";
    if (!owned_section($conn, $secId, $teacherId))     $err[] = "Choose one of your sections.";
    if ($pw !== '' && (strlen($pw) < 8 || strlen($pw) > 72)) $err[] = "Password must be 8 to 72 characters (or leave it blank to generate one).";

    if (!$err) {
        $ex = $conn->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
        $ex->execute([$sid]);
        $existing = $ex->fetch(PDO::FETCH_ASSOC);

        try {
            if ($existing) {
                // An existing student without a section can be enrolled; anything else is a clash
                $has = $conn->prepare("SELECT COUNT(*) FROM section_students WHERE student_id = ?");
                $has->execute([$sid]);
                if ($existing['role'] !== 'student' || (int)$has->fetchColumn() > 0) {
                    $err[] = "That ID is already in use.";
                } else {
                    $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)")->execute([$secId, $sid]);
                    flash_set('success', "Existing student $sid was added to the section. Their password was not changed.");
                }
            } else {
                $plain = $pw !== '' ? $pw : gen_password();
                $conn->beginTransaction();
                $conn->prepare("INSERT INTO users (user_id, fullname, password, role, status) VALUES (?, ?, ?, 'student', 'active')")
                     ->execute([$sid, $name, password_hash($plain, PASSWORD_DEFAULT)]);
                $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)")->execute([$secId, $sid]);
                $conn->commit();
                $_SESSION['cred'] = ['id' => $sid, 'name' => $name, 'password' => $plain];
                flash_set('success', "Student account created.");
            }
        } catch (PDOException $ex2) {
            if ($conn->inTransaction()) $conn->rollBack();
            $err[] = "Could not save the student. The ID may already exist.";
        }
    }

    if ($err) {
        flash_set('danger', implode("\n", $err));
    }
    header("Location: students.php?section=" . $secId);
    exit;
}

// ---- List ----
$secFilter = (int)($_GET['section'] ?? 0);
$search    = get_str('q');

$where  = "s.teacher_id = ?";
$params = [$teacherId];
if ($secFilter > 0) { $where .= " AND s.id = ?"; $params[] = $secFilter; }
if ($search !== '') {
    $where .= " AND (u.fullname LIKE ? OR u.user_id LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
$st = $conn->prepare(
    // "SELECT u.user_id, u.fullname, u.status, s.id AS section_id, s.name AS section_name, s.grade_level, s.school_year
    "SELECT u.user_id, u.fullname, u.status, s.id AS section_id, s.name AS section_name, s.grade_level, s.school_year
     FROM section_students ss
     JOIN sections s ON s.id = ss.section_id
     JOIN users u ON u.user_id = ss.student_id
     WHERE $where ORDER BY s.name, u.fullname LIMIT 500"
);
$st->execute($params);
$students = $st->fetchAll(PDO::FETCH_ASSOC);

page_top('Students', 'students.php');
?>

        <div class="welcome my-4">
            <h2>Students</h2>
            <p><?= count($students) ?> student<?= count($students) === 1 ? '' : 's' ?> shown</p>
        </div>

        <?php if (!$sections): ?>
            <div class="glass section">
                <div class="empty"><i class="bi bi-diagram-3"></i>
                    <p class="mt-2 mb-2">Create a section first, then you can add students to it.</p>
                    <a class="btn-white d-inline-flex" href="sections.php"><i class="bi bi-plus-lg"></i>Create a section</a>
                </div>
            </div>
        <?php else: ?>
        <?php if (!empty($_SESSION['bulk_cred'])): ?>
        <div class="glass section cred-card mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="m-0"><i class="bi bi-key me-2"></i>New student logins (<?= count($_SESSION['bulk_cred']) ?>)</h5>
                <div class="d-flex gap-2">
                    <a class="btn-white btn-sm-x" href="students.php?creds=csv"><i class="bi bi-download"></i>Download &amp; clear</a>
                    <a class="btn-ghost btn-sm-x" href="students.php?creds=clear" onclick="return confirm('Hide these logins? Passwords cannot be shown again.');"><i class="bi bi-x-lg"></i>Dismiss</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-glass align-middle mb-2">
                    <thead><tr><th>Student</th><th>Student ID</th><th>Password</th><th>Grade &amp; Section</th></tr></thead>
                    <tbody>
                        <?php foreach ($_SESSION['bulk_cred'] as $c): ?>
                        <tr><td><?= e($c['name']) ?></td><td><span class="mono"><?= e($c['id']) ?></span></td><td><span class="mono"><?= e($c['password']) ?></span></td><td><?= e($c['section']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small mb-0 opacity-75">Passwords are stored encrypted and can't be shown again. Download this list now and give each student their login.</p>
        </div>
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-xl-4">
                <div class="glass section glass-form">
                    <h5>Add Student</h5>
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                        <label for="student_id">Student ID (login)</label>
                        <input id="student_id" name="student_id" class="form-control mb-3" maxlength="30" placeholder="e.g. 2026-0001" required>

                        <label for="fullname">Full name</label>
                        <input id="fullname" name="fullname" class="form-control mb-3" maxlength="150" required>

                        <label for="section_id">Grade &amp; Section</label>
                        <select id="section_id" name="section_id" class="form-select mb-3">
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secFilter ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['school_year']) ?>)</option>
                            <?php endforeach; ?>
                        </select>

                        <label for="password">Password</label>
                        <input id="password" name="password" type="text" class="form-control mb-1" maxlength="72" placeholder="Leave blank to generate" autocomplete="new-password">
                        <div class="small opacity-75 mb-3">If the ID belongs to an existing student with no section, they're simply added to this section.</div>

                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-person-plus"></i>Add Student</button>
                    </form>
                </div>

                <div class="glass section glass-form mt-3">
                    <h5>Upload Students (CSV)</h5>
                    <p class="small opacity-75">Add many students at once. Download the template, fill it in Excel, then upload it.</p>
                    <a class="btn-ghost mb-3" href="students.php?template=1"><i class="bi bi-download"></i>Download template</a>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="upload_students">

                        <label for="up_section">Grade &amp; Section</label>
                        <select id="up_section" name="section_id" class="form-select mb-3" required>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secFilter ? 'selected' : '' ?>><?= e(sec_title($s)) ?> (<?= e($s['school_year']) ?>)</option>
                            <?php endforeach; ?>
                        </select>

                        <label for="up_csv">CSV file</label>
                        <input id="up_csv" type="file" name="csv" accept=".csv,text/csv" class="form-control mb-3" required>
                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-upload"></i>Upload &amp; Create</button>
                    </form>
                    <p class="small opacity-75 mt-3 mb-0">Columns: <span class="mono">student_id, fullname, password</span>. Leave <span class="mono">password</span> blank to generate one. In Excel, format the student_id column as Text so IDs like 2026-0001 aren't turned into dates.</p>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <form method="GET" class="filters mb-4">
                        <select name="section" class="form-select">
                            <option value="0">All sections</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secFilter ? 'selected' : '' ?>><?= e(sec_title($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="q" class="form-control search-input" placeholder="Search name or ID" value="<?= e($search) ?>">
                        <button class="btn-white"><i class="bi bi-search"></i><span class="d-none d-sm-inline">Search</span></button>
                        <?php if ($secFilter > 0 || $search !== ''): ?>
                            <a class="btn-ghost" href="students.php"><i class="bi bi-x-lg"></i><span class="d-none d-sm-inline">Clear</span></a>
                        <?php endif; ?>
                    </form>

                    <?php if (!$students): ?>
                        <div class="empty"><i class="bi bi-people"></i><p class="mt-2 mb-0">No students found.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-glass align-middle">
                            <!-- <thead><tr><th>Student</th><th>Student ID</th><th>Section</th><th>Status</th><th></th></tr></thead> -->
                             <thead><tr><th>Student</th><th>Student ID</th><th>Grade &amp; Section</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($students as $r): ?>
                                <tr>
                                    <td><?= e($r['fullname']) ?></td>
                                    <td><span class="mono"><?= e($r['user_id']) ?></span></td>
                                    <!-- <td><?= e($r['section_name']) ?></td> -->
                                     <td><?= e(trim((($r['grade_level'] ?? '') !== '' ? 'Grade ' . $r['grade_level'] . ' ' : '') . $r['section_name'])) ?></td>
                                    <td><span class="pill <?= $r['status'] === 'active' ? 'ok' : 'bad' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                                    <td class="text-end"><a class="btn-ghost btn-sm-x" href="student_edit.php?id=<?= e(urlencode($r['user_id'])) ?>"><i class="bi bi-pencil"></i>Manage</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

<?php page_bottom(); ?>