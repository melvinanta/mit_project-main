<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$sections = my_sections($conn, $teacherId);


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
    "SELECT u.user_id, u.fullname, u.status, s.id AS section_id, s.name AS section_name, s.school_year
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
        <div class="row g-3">
            <div class="col-xl-4">
                <div class="glass section h-100 glass-form">
                    <h5>Add Student</h5>
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                        <label for="student_id">Student ID (login)</label>
                        <input id="student_id" name="student_id" class="form-control mb-3" maxlength="30" placeholder="e.g. 2026-0001" required>

                        <label for="fullname">Full name</label>
                        <input id="fullname" name="fullname" class="form-control mb-3" maxlength="150" required>

                        <label for="section_id">Section</label>
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
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <form method="GET" class="filters mb-4">
                        <select name="section" class="form-select">
                            <option value="0">All sections</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $secFilter ? 'selected' : '' ?>><?= e($s['name']) ?></option>
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
                            <thead><tr><th>Student</th><th>Student ID</th><th>Section</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($students as $r): ?>
                                <tr>
                                    <td><?= e($r['fullname']) ?></td>
                                    <td><span class="mono"><?= e($r['user_id']) ?></span></td>
                                    <td><?= e($r['section_name']) ?></td>
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