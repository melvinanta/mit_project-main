<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$studentId = get_str('id');
$student   = $studentId !== '' ? owned_student($conn, $studentId, $teacherId) : null;
if (!$student) {
    flash_set('danger', 'Student not found.');
    header("Location: students.php");
    exit;
}

$sections = my_sections($conn, $teacherId);
$self = 'student_edit.php?id=' . urlencode($studentId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash_set('danger', 'Your session expired. Please try again.');
        header("Location: $self");
        exit;
    }
    $action = post_str('action');

    if ($action === 'save') {
        $name   = post_str('fullname');
        $secId  = (int)($_POST['section_id'] ?? 0);
        $status = post_str('status');
        $err = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) $err[] = "Enter the student's full name.";
        if (!owned_section($conn, $secId, $teacherId))     $err[] = "Choose one of your sections.";
        if (!in_array($status, ['active', 'inactive'], true)) $err[] = "Invalid status.";

        if ($err) {
            flash_set('danger', implode("\n", $err));
        } else {
            $conn->prepare("UPDATE users SET fullname = ?, status = ? WHERE user_id = ? AND role = 'student'")
                 ->execute([$name, $status, $studentId]);
            $conn->prepare("UPDATE section_students SET section_id = ? WHERE student_id = ?")
                 ->execute([$secId, $studentId]);
            flash_set('success', "Student details saved.");
        }

    } elseif ($action === 'reset') {
        $pw = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        if ($pw !== '' && (strlen($pw) < 8 || strlen($pw) > 72)) {
            flash_set('danger', "Password must be 8 to 72 characters (or leave it blank to generate one).");
        } else {
            $plain = $pw !== '' ? $pw : gen_password();
            $conn->prepare("UPDATE users SET password = ? WHERE user_id = ? AND role = 'student'")
                 ->execute([password_hash($plain, PASSWORD_DEFAULT), $studentId]);
            $_SESSION['cred'] = ['id' => $studentId, 'name' => $student['fullname'], 'password' => $plain];
            flash_set('success', "Password reset.");
        }
    }
    header("Location: $self");
    exit;
}


$g = $conn->prepare("SELECT COUNT(*) AS c, AVG(grade) AS a FROM grades WHERE student_id = ?");
$g->execute([$studentId]);
$sum = $g->fetch(PDO::FETCH_ASSOC);

$periods = $conn->prepare("SELECT DISTINCT school_year, term FROM grades WHERE student_id = ? ORDER BY school_year DESC, term");
$periods->execute([$studentId]);
$periods = $periods->fetchAll(PDO::FETCH_ASSOC);

page_top('Manage Student', 'students.php');
?>

        <div class="welcome my-4 d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h2><?= e($student['fullname']) ?></h2>
                <p class="mb-0"><span class="mono"><?= e($student['user_id']) ?></span> &middot; <?= e($student['section_name']) ?></p>
            </div>
            <a class="btn-ghost" href="students.php?section=<?= (int)$student['section_id'] ?>"><i class="bi bi-arrow-left"></i>Back to students</a>
        </div>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="glass section h-100 glass-form">
                    <h5>Details</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="save">

                        <label>Student ID</label>
                        <input class="form-control mb-3" value="<?= e($student['user_id']) ?>" disabled>

                        <label for="fullname">Full name</label>
                        <input id="fullname" name="fullname" class="form-control mb-3" maxlength="150" value="<?= e($student['fullname']) ?>" required>

                        <label for="section_id">Section</label>
                        <select id="section_id" name="section_id" class="form-select mb-3">
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === (int)$student['section_id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['school_year']) ?>)</option>
                            <?php endforeach; ?>
                        </select>

                        <label for="status">Account status</label>
                        <select id="status" name="status" class="form-select mb-1">
                            <option value="active"   <?= $student['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $student['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (cannot log in)</option>
                        </select>
                        <div class="small opacity-75 mb-3">Inactive students keep their grades but can't log in or appear in achievers.</div>

                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-check2"></i>Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="glass section mb-3 glass-form">
                    <h5>Reset Password</h5>
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="reset">
                        <label for="password">New password</label>
                        <input id="password" name="password" type="text" class="form-control mb-3" maxlength="72"
                               placeholder="Leave blank to generate one" autocomplete="new-password">
                        <button class="btn-white w-100 justify-content-center"
                                onclick="return confirm('Reset this student\'s password?');"><i class="bi bi-key"></i>Reset Password</button>
                    </form>
                </div>

                <div class="glass section">
                    <h5>Grades</h5>
                    <p class="mb-3"><?= (int)$sum['c'] ?> grade record<?= (int)$sum['c'] === 1 ? '' : 's' ?>
                        <?php if ($sum['a'] !== null): ?>&middot; average <strong><?= e(number_format((float)$sum['a'], 2)) ?></strong><?php endif; ?></p>
                    <?php if (!$periods): ?>
                        <p class="mb-0 opacity-75">No grades posted yet.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($periods as $p): ?>
                                <a class="btn-ghost btn-sm-x" target="_blank"
                                   href="print_report.php?<?= e(http_build_query(['section' => $student['section_id'], 'sy' => $p['school_year'], 'term' => $p['term'], 'student' => $studentId])) ?>">
                                    <i class="bi bi-printer"></i><?= e($p['school_year']) ?> &middot; <?= e($p['term']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php page_bottom(); ?>