<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

// Grade levels offered in the dropdown (change if your school uses other levels)
const SECTION_GRADE_LEVELS = [7,8,9,10,11,12];

// Make sure the grade_level column exists (added once, harmless afterwards)
try {
    $col = $conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sections' AND COLUMN_NAME = 'grade_level'");
    $col->execute();
    if (!(int)$col->fetchColumn()) {
        $conn->exec("ALTER TABLE sections ADD COLUMN grade_level TINYINT UNSIGNED NOT NULL DEFAULT 7");
    }
} catch (PDOException $ex) { /* no ALTER permission: run admin_migration.sql once */ }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash_set('danger', 'Your session expired. Please try again.');
        header("Location: sections.php");
        exit;
    }

    $action = post_str('action');
    $name   = post_str('name');
    $sy     = post_str('school_year');
    $gl     = (int)($_POST['grade_level'] ?? 0);
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($action === 'create' || $action === 'update') {
            if (mb_strlen($name) < 1 || mb_strlen($name) > 60) {
                throw new RuntimeException("Section name must be 1 to 60 characters.");
            }
            if (!in_array($gl, SECTION_GRADE_LEVELS, true)) {
                throw new RuntimeException("Please choose a grade level.");
            }
            if (!valid_sy($sy)) {
                throw new RuntimeException("School year must look like 2026-2027.");
            }
        }

        if ($action === 'create') {
            $st = $conn->prepare("INSERT INTO sections (name, grade_level, school_year, teacher_id) VALUES (?, ?, ?, ?)");
            $st->execute([$name, $gl, $sy, $teacherId]);
            // flash_set('success', "Section \"$name\" created.");
            flash_set('success', "Grade $gl \"$name\"  created.");

        } elseif ($action === 'update') {
            if (!owned_section($conn, $id, $teacherId)) throw new RuntimeException("Section not found.");
            $st = $conn->prepare("UPDATE sections SET name = ?, grade_level = ?, school_year = ? WHERE id = ? AND teacher_id = ?");
            $st->execute([$name, $gl, $sy, $id, $teacherId]);
            flash_set('success', "Section updated.");

        } elseif ($action === 'delete') {
            $sec = owned_section($conn, $id, $teacherId);
            if (!$sec) throw new RuntimeException("Section not found.");
            $c = $conn->prepare("SELECT COUNT(*) FROM section_students WHERE section_id = ?");
            $c->execute([$id]);
            if ((int)$c->fetchColumn() > 0) {
                throw new RuntimeException("Move or remove the students from \"{$sec['name']}\" before deleting it.");
            }
            $st = $conn->prepare("DELETE FROM sections WHERE id = ? AND teacher_id = ?");
            $st->execute([$id, $teacherId]);
            flash_set('success', "Section \"{$sec['name']}\" deleted.");
        }
    } catch (PDOException $ex) {
        // (PDOException extends RuntimeException, so it must be caught first)
        flash_set('danger', "That section name already exists for this school year.");
    } catch (RuntimeException $ex) {
        flash_set('danger', $ex->getMessage());
    }
    header("Location: sections.php");
    exit;
}

$sections = my_sections($conn, $teacherId);

// Grade level per section (read separately so it works with your existing my_sections())
$glMap = [];
$g = $conn->prepare("SELECT id, grade_level FROM sections WHERE teacher_id = ?");
$g->execute([$teacherId]);
foreach ($g->fetchAll(PDO::FETCH_ASSOC) as $row) $glMap[(int)$row['id']] = (int)$row['grade_level'];

page_top('Sections', 'sections.php');
?>

        <div class="welcome my-4">
            <h2>Sections</h2>
            <p>Create your sections, then add students to them.</p>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="glass section h-100 glass-form">
                    <h5>New Section</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="create">

                        <label for="name">Section name</label>
                        <input id="name" name="name" class="form-control mb-3" maxlength="60" placeholder="e.g. Section A" required>

                        <label for="grade_level">Grade level</label>
                        <select id="grade_level" name="grade_level" class="form-select mb-3" required>
                            <option value="" disabled selected>Choose grade level</option>
                            <?php foreach (SECTION_GRADE_LEVELS as $lv): ?>
                                <option value="<?= $lv ?>">Grade <?= $lv ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="school_year">School year</label>
                        <input id="school_year" name="school_year" class="form-control mb-3" pattern="\d{4}-\d{4}"
                               value="<?= e(default_school_year()) ?>" placeholder="2026-2027" required>

                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-plus-lg"></i>Create Section</button>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="glass section h-100">
                    <h5>My Sections</h5>
                    <?php if (!$sections): ?>
                        <div class="empty"><i class="bi bi-diagram-3"></i><p class="mt-2 mb-0">No sections yet. Create your first one.</p></div>
                    <?php else: ?>
                        <?php foreach ($sections as $s): $lvl = $glMap[(int)$s['id']] ?? 0; ?>
                        <div class="req">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <!-- <div>
                                    <strong class="fs-5"><?= e($s['name']) ?></strong>
                                    <div class="small opacity-75">
                                        <?= $lvl ? 'Grade ' . $lvl . ' &middot; ' : '' ?>S.Y. <?= e($s['school_year']) ?> &middot; <?= (int)$s['students'] ?> student<?= (int)$s['students'] === 1 ? '' : 's' ?>
                                    </div>
                                </div> -->
                                <div>
                                    <strong class="fs-5"><?= $lvl ? 'Grade ' . (int)$lvl . ' &middot; ' : '' ?><?= e($s['name']) ?></strong>
                                    <div class="small opacity-75">
                                        S.Y. <?= e($s['school_year']) ?> &middot; <?= (int)$s['students'] ?> student<?= (int)$s['students'] === 1 ? '' : 's' ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a class="btn-ghost btn-sm-x" href="students.php?section=<?= (int)$s['id'] ?>"><i class="bi bi-people"></i>Students</a>
                                    <a class="btn-ghost btn-sm-x" href="reports.php?section=<?= (int)$s['id'] ?>&sy=<?= e(urlencode($s['school_year'])) ?>&term=<?= e(urlencode(TERMS[0])) ?>"><i class="bi bi-bar-chart-line"></i>Report</a>
                                </div>
                            </div>

                            <details class="edit mt-3 glass-form">
                                <summary>Edit or delete</summary>
                                <form method="POST" class="row g-2 mt-1">
                                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                    <div class="col-md-4"><input name="name" class="form-control" maxlength="60" value="<?= e($s['name']) ?>" aria-label="Section name" required></div>
                                    <div class="col-md-2">
                                        <select name="grade_level" class="form-select" aria-label="Grade level" required>
                                            <?php foreach (SECTION_GRADE_LEVELS as $x): ?>
                                                <option value="<?= $x ?>" <?= $x === $lvl ? 'selected' : '' ?>>Grade <?= $x ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3"><input name="school_year" class="form-control" pattern="\d{4}-\d{4}" value="<?= e($s['school_year']) ?>" aria-label="School year" required></div>
                                    <div class="col-md-3 d-flex gap-2">
                                        <button class="btn-white flex-fill justify-content-center" name="action" value="update">Save</button>
                                        <button class="btn-danger-x" name="action" value="delete" title="Delete section"
                                                onclick="return confirm('Delete this section? This only works if it has no students.');"><i class="bi bi-trash"></i></button>
                                    </div>
                                </form>
                            </details>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php page_bottom(); ?>