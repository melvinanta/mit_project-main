<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$in = "'" . implode("','", TEACHER_ROLES) . "'";

function teacher_row(PDO $conn, string $uid, string $in): ?array {
    $st = $conn->prepare("SELECT user_id, fullname, status FROM users WHERE user_id = ? AND role IN ($in) LIMIT 1");
    $st->execute([$uid]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash_set('danger', 'Your session expired. Please try again.');
        header("Location: teachers.php"); exit;
    }

    $action = post_str('action');
    $uid    = post_str('user_id');
    $name   = post_str('fullname');
    $pw     = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    try {
        if ($action === 'create') {
            if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $uid)) throw new RuntimeException("Teacher ID must be 3-30 characters: letters, numbers, dot, dash or underscore.");
            if (mb_strlen($name) < 2 || mb_strlen($name) > 150) throw new RuntimeException("Enter the teacher's full name.");
            if ($pw !== '' && (strlen($pw) < 8 || strlen($pw) > 72)) throw new RuntimeException("Password must be 8 to 72 characters (or leave it blank to generate one).");

            $x = $conn->prepare("SELECT 1 FROM users WHERE user_id = ? LIMIT 1");
            $x->execute([$uid]);
            if ($x->fetch()) throw new RuntimeException("That ID is already in use.");

            $plain = $pw !== '' ? $pw : gen_password();
            $conn->prepare("INSERT INTO users (user_id, fullname, password, role, status) VALUES (?, ?, ?, ?, 'active')")
                 ->execute([$uid, $name, password_hash($plain, PASSWORD_DEFAULT), new_teacher_role($conn)]);
            $_SESSION['cred'] = ['label' => 'Teacher', 'id' => $uid, 'name' => $name, 'password' => $plain];
            flash_set('success', "Teacher account created.");

        } elseif ($action === 'rename') {
            if (!teacher_row($conn, $uid, $in)) throw new RuntimeException("Teacher not found.");
            if (mb_strlen($name) < 2 || mb_strlen($name) > 150) throw new RuntimeException("Enter the teacher's full name.");
            $conn->prepare("UPDATE users SET fullname = ? WHERE user_id = ?")->execute([$name, $uid]);
            flash_set('success', "Name updated.");

        } elseif ($action === 'reset') {
            $t = teacher_row($conn, $uid, $in);
            if (!$t) throw new RuntimeException("Teacher not found.");
            if ($pw !== '' && (strlen($pw) < 8 || strlen($pw) > 72)) throw new RuntimeException("Password must be 8 to 72 characters (or leave it blank to generate one).");
            $plain = $pw !== '' ? $pw : gen_password();
            $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([password_hash($plain, PASSWORD_DEFAULT), $uid]);
            $_SESSION['cred'] = ['label' => 'Teacher', 'id' => $uid, 'name' => $t['fullname'], 'password' => $plain];
            flash_set('success', "Password reset.");

        } elseif ($action === 'activate' || $action === 'deactivate') {
            $t = teacher_row($conn, $uid, $in);
            if (!$t) throw new RuntimeException("Teacher not found.");
            $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$action === 'activate' ? 'active' : 'inactive', $uid]);
            flash_set('success', $t['fullname'] . ($action === 'activate' ? " can log in again." : " can no longer log in."));

        } elseif ($action === 'delete') {
            $t = teacher_row($conn, $uid, $in);
            if (!$t) throw new RuntimeException("Teacher not found.");
            $c = $conn->prepare("SELECT COUNT(*) FROM sections WHERE teacher_id = ?");
            $c->execute([$uid]);
            if ((int)$c->fetchColumn() > 0) throw new RuntimeException("{$t['fullname']} still has sections. Reassign them on the Sections page first (or deactivate the account instead).");
            $conn->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
            flash_set('success', "Teacher account deleted.");
        }
    } catch (PDOException $ex) {   // (PDOException extends RuntimeException, so it must be caught first)
        flash_set('danger', "Could not save that change.");
    } catch (RuntimeException $ex) {
        flash_set('danger', $ex->getMessage());
    }
    header("Location: teachers.php"); exit;
}


$search = get_str('q');
$where  = "u.role IN ($in)"; $params = [];
if ($search !== '') { $where .= " AND (u.fullname LIKE ? OR u.user_id LIKE ?)"; $params = ["%$search%", "%$search%"]; }
$st = $conn->prepare(
    "SELECT u.user_id, u.fullname, u.status,
            (SELECT COUNT(*) FROM sections s WHERE s.teacher_id = u.user_id) AS sections,
            (SELECT COUNT(*) FROM section_students ss JOIN sections s2 ON s2.id = ss.section_id WHERE s2.teacher_id = u.user_id) AS students
     FROM users u WHERE $where ORDER BY u.fullname LIMIT 500"
);
$st->execute($params);
$teachers = $st->fetchAll(PDO::FETCH_ASSOC);

page_top('Teachers', 'teachers.php');
?>

        <div class="welcome my-4">
            <h2>Teachers</h2>
            <p><?= count($teachers) ?> teacher<?= count($teachers) === 1 ? '' : 's' ?> shown</p>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="glass section glass-form">
                    <h5>Add Teacher</h5>
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="create">

                        <label for="user_id">Teacher ID (login)</label>
                        <input id="user_id" name="user_id" class="form-control mb-3" maxlength="30" placeholder="e.g. t.santos" required>

                        <label for="fullname">Full name</label>
                        <input id="fullname" name="fullname" class="form-control mb-3" maxlength="150" required>

                        <label for="password">Password</label>
                        <input id="password" name="password" type="text" class="form-control mb-1" maxlength="72" placeholder="Leave blank to generate" autocomplete="new-password">
                        <div class="small opacity-75 mb-3">The password is shown once after you save. Teachers log in with the Teacher button.</div>

                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-person-plus"></i>Create Teacher</button>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <form method="GET" class="filters mb-4">
                        <input type="text" name="q" class="form-control search-input" placeholder="Search name or ID" value="<?= e($search) ?>">
                        <button class="btn-white"><i class="bi bi-search"></i><span class="d-none d-sm-inline">Search</span></button>
                        <?php if ($search !== ''): ?><a class="btn-ghost" href="teachers.php"><i class="bi bi-x-lg"></i><span class="d-none d-sm-inline">Clear</span></a><?php endif; ?>
                    </form>

                    <?php if (!$teachers): ?>
                        <div class="empty"><i class="bi bi-person-badge"></i><p class="mt-2 mb-0">No teachers found.</p></div>
                    <?php else: foreach ($teachers as $t): $active = $t['status'] === 'active'; ?>
                    <div class="req">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong class="fs-5"><?= e($t['fullname']) ?></strong>
                                <span class="pill <?= $active ? 'ok' : 'bad' ?> ms-2"><?= $active ? 'Active' : 'Inactive' ?></span>
                                <div class="small opacity-75"><span class="mono"><?= e($t['user_id']) ?></span> &middot; <?= (int)$t['sections'] ?> section<?= (int)$t['sections'] === 1 ? '' : 's' ?> &middot; <?= (int)$t['students'] ?> student<?= (int)$t['students'] === 1 ? '' : 's' ?></div>
                            </div>
                            <a class="btn-ghost btn-sm-x" href="sections.php?teacher=<?= e(urlencode($t['user_id'])) ?>"><i class="bi bi-diagram-3"></i>Sections</a>
                        </div>

                        <details class="edit mt-3 glass-form">
                            <summary>Manage account</summary>
                            <form method="POST" class="row g-2 mt-1">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                <input type="hidden" name="user_id" value="<?= e($t['user_id']) ?>">
                                <div class="col-md-5"><input name="fullname" class="form-control" maxlength="150" value="<?= e($t['fullname']) ?>" aria-label="Full name"></div>
                                <div class="col-md-3"><button class="btn-white w-100 justify-content-center" name="action" value="rename">Save name</button></div>
                                <div class="col-md-4">
                                    <?php if ($active): ?>
                                        <button class="btn-ghost w-100 justify-content-center" name="action" value="deactivate" onclick="return confirm('Deactivate this teacher? They will not be able to log in.');"><i class="bi bi-slash-circle"></i>Deactivate</button>
                                    <?php else: ?>
                                        <button class="btn-ghost w-100 justify-content-center" name="action" value="activate"><i class="bi bi-check-circle"></i>Activate</button>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-5"><input name="password" type="text" class="form-control" maxlength="72" placeholder="New password (blank = generate)" autocomplete="new-password" aria-label="New password"></div>
                                <div class="col-md-3"><button class="btn-ghost w-100 justify-content-center" name="action" value="reset" onclick="return confirm('Reset this teacher\'s password?');"><i class="bi bi-key"></i>Reset</button></div>
                                <div class="col-md-4 text-md-end">
                                    <button class="btn-danger-x" name="action" value="delete" title="Delete teacher" onclick="return confirm('Delete this teacher account? This cannot be undone.');"><i class="bi bi-trash"></i></button>
                                </div>
                            </form>
                        </details>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

<?php page_bottom(); ?>