<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$teachers = all_teachers($conn);
$tids = array_column($teachers, 'user_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash_set('danger', 'Your session expired. Please try again.'); header("Location: sections.php"); exit; }

    $action = post_str('action');
    $id     = (int)($_POST['id'] ?? 0);
    $name   = post_str('name');
    $level  = post_str('grade_level');
    $sy     = post_str('school_year');
    $tid    = post_str('teacher_id');

    try {
        if ($action === 'create' || $action === 'update') {
            if (mb_strlen($name) < 2 || mb_strlen($name) > 60)   throw new RuntimeException("Section name must be 2 to 60 characters.");
            if (!in_array($level, GRADE_LEVELS, true))           throw new RuntimeException("Please choose a grade level.");
            if (!valid_sy($sy))                                  throw new RuntimeException("School year must look like 2026-2027.");
            if (!in_array($tid, $tids, true))                    throw new RuntimeException("Please choose a teacher.");
        }
        if ($action === 'create') {
            $conn->prepare("INSERT INTO sections (name, grade_level, school_year, teacher_id) VALUES (?, ?, ?, ?)")->execute([$name, $level, $sy, $tid]);
            flash_set('success', "Section \"$name\" created.");

        } elseif ($action === 'update') {
            if (!get_section($conn, $id)) throw new RuntimeException("Section not found.");
            $conn->prepare("UPDATE sections SET name = ?, grade_level = ?, school_year = ?, teacher_id = ? WHERE id = ?")->execute([$name, $level, $sy, $tid, $id]);
            flash_set('success', "Section updated.");

        } elseif ($action === 'delete') {
            $sec = get_section($conn, $id);
            if (!$sec) throw new RuntimeException("Section not found.");
            $c = $conn->prepare("SELECT COUNT(*) FROM section_students WHERE section_id = ?");
            $c->execute([$id]);
            if ((int)$c->fetchColumn() > 0) throw new RuntimeException("Move or remove the students from \"" . sec_title($sec) . "\" before deleting it.");
            $conn->prepare("DELETE FROM sections WHERE id = ?")->execute([$id]);
            flash_set('success', "Section deleted.");
        }
    } catch (PDOException $ex) {   // (must be caught before RuntimeException)
        flash_set('danger', "That teacher already has a section with this name for this school year.");
    } catch (RuntimeException $ex) {
        flash_set('danger', $ex->getMessage());
    }
    header("Location: sections.php"); exit;
}


$fLevel   = get_str('level');
$fTeacher = get_str('teacher');
$where = '1=1'; $params = [];
if ($fLevel !== '')   { $where .= " AND s.grade_level = ?"; $params[] = $fLevel; }
if ($fTeacher !== '') { $where .= " AND s.teacher_id = ?";  $params[] = $fTeacher; }
$st = $conn->prepare(
    "SELECT s.id, s.name, s.grade_level, s.school_year, s.teacher_id, t.fullname AS teacher,
            (SELECT COUNT(*) FROM section_students ss WHERE ss.section_id = s.id) AS students
     FROM sections s LEFT JOIN users t ON t.user_id = s.teacher_id
     WHERE $where ORDER BY s.school_year DESC, s.grade_level, s.name"
);
$st->execute($params);
$sections = $st->fetchAll(PDO::FETCH_ASSOC);

function teacher_options(array $teachers, string $sel): string {
    $o = '';
    foreach ($teachers as $t) {
        $o .= '<option value="' . e($t['user_id']) . '"' . ($t['user_id'] === $sel ? ' selected' : '') . '>' . e($t['fullname']) . ($t['status'] === 'active' ? '' : ' (inactive)') . '</option>';
    }
    return $o;
}
function level_options(string $sel): string {
    $o = '';
    foreach (GRADE_LEVELS as $g) { $o .= '<option' . ($g === $sel ? ' selected' : '') . '>' . e($g) . '</option>'; }
    return $o;
}

page_top('Sections', 'sections.php');
?>

        <div class="welcome my-4">
            <h2>Sections</h2>
            <p><?= count($sections) ?> section<?= count($sections) === 1 ? '' : 's' ?> shown. Create sections, set the grade level and assign or change the teacher.</p>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="glass section glass-form">
                    <h5>New Section</h5>
                    <?php if (!$teachers): ?>
                        <p class="opacity-75">Add a teacher first, then create sections for them.</p>
                        <a class="btn-white d-inline-flex" href="teachers.php"><i class="bi bi-person-plus"></i>Add a teacher</a>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="create">

                        <label for="grade_level">Grade level</label>
                        <select id="grade_level" name="grade_level" class="form-select mb-3" required><option value="">Choose…</option><?= level_options('') ?></select>

                        <label for="name">Section name</label>
                        <input id="name" name="name" class="form-control mb-3" maxlength="60" placeholder="e.g. Sharks" required>

                        <label for="school_year">School year</label>
                        <input id="school_year" name="school_year" class="form-control mb-3" pattern="\d{4}-\d{4}" value="<?= e(default_school_year()) ?>" required>

                        <label for="teacher_id">Teacher</label>
                        <select id="teacher_id" name="teacher_id" class="form-select mb-3" required><option value="">Choose…</option><?= teacher_options($teachers, $fTeacher) ?></select>

                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-plus-lg"></i>Create Section</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <form method="GET" class="filters mb-4">
                        <select name="level" class="form-select">
                            <option value="">All grade levels</option>
                            <?= level_options($fLevel) ?>
                        </select>
                        <select name="teacher" class="form-select">
                            <option value="">All teachers</option>
                            <?= teacher_options($teachers, $fTeacher) ?>
                        </select>
                        <button class="btn-white"><i class="bi bi-funnel"></i><span class="d-none d-sm-inline">Filter</span></button>
                        <?php if ($fLevel !== '' || $fTeacher !== ''): ?><a class="btn-ghost" href="sections.php"><i class="bi bi-x-lg"></i><span class="d-none d-sm-inline">Clear</span></a><?php endif; ?>
                    </form>

                    <?php if (!$sections): ?>
                        <div class="empty"><i class="bi bi-diagram-3"></i><p class="mt-2 mb-0">No sections found.</p></div>
                    <?php else: foreach ($sections as $s): ?>
                    <div class="req">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong class="fs-5"><?= e(sec_title($s)) ?></strong>
                                <div class="small opacity-75">S.Y. <?= e($s['school_year']) ?> &middot; Teacher: <?= e($s['teacher'] ?: 'Unassigned') ?> &middot; <?= (int)$s['students'] ?> student<?= (int)$s['students'] === 1 ? '' : 's' ?></div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn-ghost btn-sm-x" href="students.php?section=<?= (int)$s['id'] ?>"><i class="bi bi-people"></i>Students</a>
                                <a class="btn-ghost btn-sm-x" href="reports.php?<?= e(http_build_query(['type' => 'section', 'section' => $s['id'], 'sy' => $s['school_year'], 'term' => TERMS[0]])) ?>"><i class="bi bi-bar-chart-line"></i>Report</a>
                            </div>
                        </div>

                        <details class="edit mt-3 glass-form">
                            <summary>Edit or delete</summary>
                            <form method="POST" class="row g-2 mt-1">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                <div class="col-md-3"><select name="grade_level" class="form-select" aria-label="Grade level" required><option value="">Grade level…</option><?= level_options((string)$s['grade_level']) ?></select></div>
                                <div class="col-md-3"><input name="name" class="form-control" maxlength="60" value="<?= e($s['name']) ?>" aria-label="Section name" required></div>
                                <div class="col-md-2"><input name="school_year" class="form-control" pattern="\d{4}-\d{4}" value="<?= e($s['school_year']) ?>" aria-label="School year" required></div>
                                <div class="col-md-4"><select name="teacher_id" class="form-select" aria-label="Teacher" required><option value="">Teacher…</option><?= teacher_options($teachers, (string)$s['teacher_id']) ?></select></div>
                                <div class="col-12 d-flex gap-2">
                                    <button class="btn-white justify-content-center" name="action" value="update">Save changes</button>
                                    <button class="btn-danger-x" name="action" value="delete" title="Delete section" onclick="return confirm('Delete this section? This only works if it has no students.');"><i class="bi bi-trash"></i></button>
                                </div>
                            </form>
                        </details>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

<?php page_bottom(); ?>