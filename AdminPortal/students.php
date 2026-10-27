<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$allSections = all_sections($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = 'students.php' . (isset($_POST['back']) && is_string($_POST['back']) && preg_match('/^\?[A-Za-z0-9=&%_.+-]*$/', $_POST['back']) ? $_POST['back'] : '');
    if (!csrf_ok()) { flash_set('danger', 'Your session expired. Please try again.'); header("Location: $back"); exit; }

    $action = post_str('action');
    $uid    = post_str('user_id');
    $st = $conn->prepare("SELECT user_id, fullname, status FROM users WHERE user_id = ? AND role = 'student' LIMIT 1");
    $st->execute([$uid]);
    $stu = $st->fetch(PDO::FETCH_ASSOC);

    try {
        if (!$stu) throw new RuntimeException("Student not found.");

        if ($action === 'reset') {
            $plain = gen_password(8);
            $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([password_hash($plain, PASSWORD_DEFAULT), $uid]);
            $_SESSION['cred'] = ['label' => 'Student', 'id' => $uid, 'name' => $stu['fullname'], 'password' => $plain];
            flash_set('success', "Password reset.");

        } elseif ($action === 'activate' || $action === 'deactivate') {
            $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$action === 'activate' ? 'active' : 'inactive', $uid]);
            flash_set('success', $stu['fullname'] . ($action === 'activate' ? " is active again." : " was deactivated."));

        } elseif ($action === 'move') {
            $secId = (int)($_POST['section_id'] ?? 0);
            if (!get_section($conn, $secId)) throw new RuntimeException("Choose a section.");
            $has = $conn->prepare("SELECT COUNT(*) FROM section_students WHERE student_id = ?");
            $has->execute([$uid]);
            if ((int)$has->fetchColumn() > 0) {
                $conn->prepare("UPDATE section_students SET section_id = ? WHERE student_id = ?")->execute([$secId, $uid]);
            } else {
                $conn->prepare("INSERT INTO section_students (section_id, student_id) VALUES (?, ?)")->execute([$secId, $uid]);
            }
            flash_set('success', $stu['fullname'] . " was moved.");
        }
    } catch (PDOException $ex) {
        flash_set('danger', "Could not save that change.");
    } catch (RuntimeException $ex) {
        flash_set('danger', $ex->getMessage());
    }
    header("Location: $back"); exit;
}


$fLevel  = get_str('level');
$fSec    = (int)($_GET['section'] ?? 0);
$fStatus = get_str('status');
$search  = get_str('q');

$where = "u.role = 'student'"; $params = [];
if ($fLevel !== '')  { $where .= " AND s.grade_level = ?"; $params[] = $fLevel; }
if ($fSec > 0)       { $where .= " AND s.id = ?"; $params[] = $fSec; }
if (in_array($fStatus, ['active', 'inactive'], true)) { $where .= " AND u.status = ?"; $params[] = $fStatus; }
if ($search !== '')  { $where .= " AND (u.fullname LIKE ? OR u.user_id LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$st = $conn->prepare(
    "SELECT u.user_id, u.fullname, u.status, s.id AS section_id, s.name, s.grade_level, s.school_year, t.fullname AS teacher
     FROM users u
     LEFT JOIN section_students ss ON ss.student_id = u.user_id
     LEFT JOIN sections s ON s.id = ss.section_id
     LEFT JOIN users t ON t.user_id = s.teacher_id
     WHERE $where ORDER BY s.grade_level, s.name, u.fullname LIMIT 300"
);
$st->execute($params);
$students = $st->fetchAll(PDO::FETCH_ASSOC);

$levelsInUse = GRADE_LEVELS;
$backQs = $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';

page_top('Students', 'students.php');
?>

        <div class="welcome my-4">
            <h2>Students</h2>
            <p><?= count($students) ?> student<?= count($students) === 1 ? '' : 's' ?> shown<?= count($students) === 300 ? ' (first 300 &mdash; narrow the filters to see others)' : '' ?></p>
        </div>

        <div class="glass section">
            <form method="GET" class="filters mb-4">
                <select name="level" class="form-select" aria-label="Grade level">
                    <option value="">All grade levels</option>
                    <?php foreach ($levelsInUse as $g): ?><option <?= $g === $fLevel ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
                </select>
                <select name="section" class="form-select" aria-label="Section">
                    <option value="0">All sections</option>
                    <?php foreach ($allSections as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === $fSec ? 'selected' : '' ?>><?= e(sec_title($s)) ?> (<?= e($s['school_year']) ?>)</option><?php endforeach; ?>
                </select>
                <select name="status" class="form-select" aria-label="Status">
                    <option value="">Any status</option>
                    <option value="active" <?= $fStatus === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $fStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <input type="text" name="q" class="form-control search-input" placeholder="Search name or ID" value="<?= e($search) ?>">
                <button class="btn-white"><i class="bi bi-search"></i><span class="d-none d-sm-inline">Search</span></button>
                <?php if ($fLevel !== '' || $fSec > 0 || $fStatus !== '' || $search !== ''): ?><a class="btn-ghost" href="students.php"><i class="bi bi-x-lg"></i><span class="d-none d-sm-inline">Clear</span></a><?php endif; ?>
            </form>

            <?php if (!$students): ?>
                <div class="empty"><i class="bi bi-people"></i><p class="mt-2 mb-0">No students found.</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-glass align-middle">
                    <thead><tr><th>Student</th><th>Student ID</th><th>Grade &amp; Section</th><th>Teacher</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($students as $r): $active = $r['status'] === 'active'; ?>
                        <tr>
                            <td><?= e($r['fullname']) ?></td>
                            <td><span class="mono"><?= e($r['user_id']) ?></span></td>
                            <td><?= $r['name'] !== null ? e(sec_title($r)) : '<span class="opacity-75">No section</span>' ?></td>
                            <td><?= e($r['teacher'] ?: '—') ?></td>
                            <td><span class="pill <?= $active ? 'ok' : 'bad' ?>"><?= $active ? 'Active' : 'Inactive' ?></span></td>
                            <td class="text-end">
                                <details class="edit glass-form">
                                    <summary>Manage</summary>
                                    <form method="POST" class="d-flex flex-wrap gap-2 justify-content-end mt-2">
                                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="user_id" value="<?= e($r['user_id']) ?>">
                                        <input type="hidden" name="back" value="<?= e($backQs) ?>">
                                        <select name="section_id" class="form-select" style="width:auto;max-width:230px" aria-label="Move to section">
                                            <?php foreach ($allSections as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === (int)$r['section_id'] ? 'selected' : '' ?>><?= e(sec_title($s)) ?> (<?= e($s['school_year']) ?>)</option><?php endforeach; ?>
                                        </select>
                                        <button class="btn-white btn-sm-x" name="action" value="move">Move</button>
                                        <button class="btn-ghost btn-sm-x" name="action" value="reset" onclick="return confirm('Reset this student\'s password?');"><i class="bi bi-key"></i>Reset</button>
                                        <?php if ($active): ?>
                                            <button class="btn-ghost btn-sm-x" name="action" value="deactivate" onclick="return confirm('Deactivate this student?');">Deactivate</button>
                                        <?php else: ?>
                                            <button class="btn-ghost btn-sm-x" name="action" value="activate">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </details>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

<?php page_bottom(); ?>