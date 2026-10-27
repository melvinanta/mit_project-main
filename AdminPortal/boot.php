<?php

ini_set("session.cookie_httponly", 1);
ini_set("session.use_strict_mode", 1);

session_start();
require __DIR__ . "/../db.php"; 

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    if (defined('API_MODE')) {
        http_response_code(401);
        header('Content-Type: application/json');
        exit('{"error":"unauthorized"}');
    }
    header("Location: ../index.php");
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


define('PASSING_GRADE', 75);
const TERMS        = ['1st Semester', '2nd Semester', 'Summer'];
const GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
const TEACHER_ROLES = ['teacher', 'subteacher'];   // both are treated as teachers
const HONOR_LEVELS = [[98, 'With Highest Honors'], [95, 'With High Honors'], [90, 'With Honors']];


$adminId  = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'Administrator';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_ok(): bool { return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']); }
function get_str(string $k): string  { return (isset($_GET[$k])  && is_string($_GET[$k]))  ? trim($_GET[$k])  : ''; }
function post_str(string $k): string { return (isset($_POST[$k]) && is_string($_POST[$k])) ? trim($_POST[$k]) : ''; }

function fmt_grade($g): string { return ($g === null || $g === '') ? '—' : number_format((float)$g, 2); }
function default_school_year(): string {
    $y = (int)date('Y');
    return ((int)date('n') >= 6) ? "$y-" . ($y + 1) : ($y - 1) . "-$y";
}
function valid_sy(string $sy): bool { return (bool)preg_match('/^\d{4}-\d{4}$/', $sy); }

function gen_password(int $len = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) { $out .= $chars[random_int(0, strlen($chars) - 1)]; }
    return $out;
}

function flash_set(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function flash_get(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

function csv_safe($v) {
    $v = (string)$v;
    return (strlen($v) && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
}


function level_label($gl): string {
    $gl = trim((string)$gl);
    if ($gl === '') return 'Unassigned';
    return stripos($gl, 'grade') === 0 ? $gl : 'Grade ' . $gl;
}

function sec_title(array $s): string {
    $gl = trim((string)($s['grade_level'] ?? ''));
    return $gl === '' ? (string)$s['name'] : level_label($gl) . ' ' . $s['name'];
}


function new_teacher_role(PDO $conn): string {
    try {
        $r = $conn->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
        if ($r && stripos((string)$r['Type'], "'teacher'") !== false) return 'teacher';
    } catch (Throwable $t) {}
    return 'subteacher';
}

function all_sections(PDO $conn): array {
    $st = $conn->query(
        "SELECT s.id, s.name, s.grade_level, s.school_year, s.teacher_id, t.fullname AS teacher
         FROM sections s LEFT JOIN users t ON t.user_id = s.teacher_id
         ORDER BY s.school_year DESC, s.grade_level, s.name"
    );
    return $st->fetchAll(PDO::FETCH_ASSOC);
}
function get_section(PDO $conn, int $id): ?array {
    if ($id <= 0) return null;
    $st = $conn->prepare(
        "SELECT s.id, s.name, s.grade_level, s.school_year, s.teacher_id, t.fullname AS teacher
         FROM sections s LEFT JOIN users t ON t.user_id = s.teacher_id WHERE s.id = ? LIMIT 1"
    );
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
function all_teachers(PDO $conn): array {
    $in = "'" . implode("','", TEACHER_ROLES) . "'";
    return $conn->query("SELECT user_id, fullname, status FROM users WHERE role IN ($in) ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);
}



function honor_label(float $avg): ?string {
    foreach (HONOR_LEVELS as $lvl) { if ($avg >= $lvl[0]) return $lvl[1]; }
    return null;
}

function section_ranking(PDO $conn, int $sectionId, string $sy = '', string $term = ''): array {
    $pass = (int)PASSING_GRADE;
    $st = $conn->prepare(
        "SELECT ss.student_id, u.fullname, AVG(g.grade) AS avg_grade, COUNT(g.id) AS total,
                SUM(CASE WHEN g.grade IS NOT NULL AND g.grade < $pass THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN g.id IS NOT NULL AND g.grade IS NULL THEN 1 ELSE 0 END) AS inc
         FROM section_students ss
         JOIN users u ON u.user_id = ss.student_id
         LEFT JOIN grades g ON g.student_id = ss.student_id
              AND (? = '' OR g.school_year = ?) AND (? = '' OR g.term = ?)
         WHERE ss.section_id = ? AND u.status = 'active'
         GROUP BY ss.student_id, u.fullname"
    );
    $st->execute([$sy, $sy, $term, $term, $sectionId]);

    $ranked = []; $unranked = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $r['avg']    = $r['avg_grade'] !== null ? round((float)$r['avg_grade'], 2) : null;
        $r['failed'] = (int)$r['failed'];
        $r['inc']    = (int)$r['inc'];
        $r['honor']  = ($r['avg'] !== null && $r['failed'] === 0 && $r['inc'] === 0) ? honor_label($r['avg']) : null;
        $r['rank']   = null;
        if ($r['avg'] !== null) { $ranked[] = $r; } else { $unranked[] = $r; }
    }
    usort($ranked, fn($a, $b) => ($b['avg'] <=> $a['avg']) ?: strcasecmp($a['fullname'], $b['fullname']));
    usort($unranked, fn($a, $b) => strcasecmp($a['fullname'], $b['fullname']));
    return rank_rows(array_merge($ranked, $unranked));
}


function rank_rows(array $rows): array {
    $last = 0; $lastAvg = null; $i = 0;
    foreach ($rows as $k => $r) {
        if ($r['avg'] === null) { $rows[$k]['rank'] = null; continue; }
        $i++;
        $rank = ($lastAvg !== null && $r['avg'] == $lastAvg) ? $last : $i;
        $rows[$k]['rank'] = $rank; $last = $rank; $lastAvg = $r['avg'];
    }
    return $rows;
}

function row_remarks(array $r): string {
    if ($r['avg'] === null) return 'No grades';
    if ($r['honor'])        return $r['honor'];
    if ($r['failed'] > 0)   return 'Has failed subject(s)';
    if ($r['inc'] > 0)      return 'Has INC';
    return 'Passed';
}


function build_report(PDO $conn, int $sectionId, string $sy, string $term): array {
    $st = $conn->prepare(
        "SELECT DISTINCT g.subject_code, g.subject_name FROM grades g
         JOIN section_students ss ON ss.student_id = g.student_id
         WHERE ss.section_id = ? AND g.school_year = ? AND g.term = ? ORDER BY g.subject_code"
    );
    $st->execute([$sectionId, $sy, $term]);
    $subjects = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $conn->prepare(
        "SELECT g.student_id, g.subject_code, g.grade FROM grades g
         JOIN section_students ss ON ss.student_id = g.student_id
         WHERE ss.section_id = ? AND g.school_year = ? AND g.term = ?"
    );
    $st->execute([$sectionId, $sy, $term]);
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $g) { $map[(string)$g['student_id']][$g['subject_code']] = $g['grade']; }

    $rows = section_ranking($conn, $sectionId, $sy, $term);
    foreach ($rows as $i => $r) { $rows[$i]['grades'] = $map[(string)$r['student_id']] ?? []; }
    return [$subjects, $rows];
}



function level_sort_key(string $level): array {
    if ($level === 'Unassigned') return [1, 0, $level];
    return [0, preg_match('/(\d+)/', $level, $m) ? (int)$m[1] : 999, $level];
}

function analytics(PDO $conn, string $sy = '', string $term = ''): array {
    $pass = (int)PASSING_GRADE;
    $in   = "'" . implode("','", TEACHER_ROLES) . "'";
    $one  = fn(string $sql) => (int)$conn->query($sql)->fetchColumn();

    // Sections (limited to the school year when one is chosen)
    $st = $conn->prepare(
        "SELECT s.id, s.name, s.grade_level, s.school_year, t.fullname AS teacher
         FROM sections s LEFT JOIN users t ON t.user_id = s.teacher_id
         WHERE (? = '' OR s.school_year = ?) ORDER BY s.name"
    );
    $st->execute([$sy, $sy]);
    $secRows = $st->fetchAll(PDO::FETCH_ASSOC);

    $studentsBySec = [];
    foreach ($conn->query(
        "SELECT ss.section_id, COUNT(*) AS c FROM section_students ss
         JOIN users u ON u.user_id = ss.student_id AND u.status = 'active' GROUP BY ss.section_id"
    )->fetchAll(PDO::FETCH_ASSOC) as $r) { $studentsBySec[(int)$r['section_id']] = (int)$r['c']; }

    $st = $conn->prepare(
        "SELECT ss.section_id, COUNT(g.id) AS n,
                SUM(CASE WHEN g.grade IS NOT NULL THEN g.grade ELSE 0 END) AS total,
                SUM(CASE WHEN g.grade IS NOT NULL THEN 1 ELSE 0 END) AS graded,
                SUM(CASE WHEN g.grade >= $pass THEN 1 ELSE 0 END) AS passed,
                SUM(CASE WHEN g.grade IS NOT NULL AND g.grade < $pass THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN g.id IS NOT NULL AND g.grade IS NULL THEN 1 ELSE 0 END) AS inc
         FROM section_students ss
         JOIN users u ON u.user_id = ss.student_id AND u.status = 'active'
         JOIN grades g ON g.student_id = ss.student_id AND (? = '' OR g.school_year = ?) AND (? = '' OR g.term = ?)
         GROUP BY ss.section_id"
    );
    $st->execute([$sy, $sy, $term, $term]);
    $gradeBySec = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $gradeBySec[(int)$r['section_id']] = $r; }

    $sections = []; $levels = []; $outcomes = ['passed' => 0, 'failed' => 0, 'inc' => 0]; $gradesPosted = 0;
    foreach ($secRows as $s) {
        $id = (int)$s['id']; $g = $gradeBySec[$id] ?? null;
        $graded = $g ? (int)$g['graded'] : 0; $passed = $g ? (int)$g['passed'] : 0;
        $failed = $g ? (int)$g['failed'] : 0; $inc = $g ? (int)$g['inc'] : 0; $total = $g ? (float)$g['total'] : 0.0;
        $lvl = level_label($s['grade_level']);
        $students = $studentsBySec[$id] ?? 0;

        $sections[] = [
            'id' => $id, 'label' => sec_title($s), 'level' => $lvl, 'teacher' => $s['teacher'] ?: '—',
            'school_year' => $s['school_year'], 'students' => $students,
            'avg' => $graded ? round($total / $graded, 2) : null,
            'pass_rate' => ($passed + $failed) ? round(100 * $passed / ($passed + $failed), 1) : null,
            'failed' => $failed, 'inc' => $inc, 'grades' => $graded + $inc,
        ];
        if (!isset($levels[$lvl])) $levels[$lvl] = ['level' => $lvl, 'sections' => 0, 'students' => 0, 'total' => 0.0, 'graded' => 0, 'passed' => 0, 'failed' => 0, 'inc' => 0];
        $L =& $levels[$lvl];
        $L['sections']++; $L['students'] += $students; $L['total'] += $total; $L['graded'] += $graded;
        $L['passed'] += $passed; $L['failed'] += $failed; $L['inc'] += $inc;
        unset($L);
        $outcomes['passed'] += $passed; $outcomes['failed'] += $failed; $outcomes['inc'] += $inc;
        $gradesPosted += $graded + $inc;
    }

    $levelList = [];
    foreach ($levels as $L) {
        $levelList[] = [
            'level' => $L['level'], 'sections' => $L['sections'], 'students' => $L['students'],
            'avg' => $L['graded'] ? round($L['total'] / $L['graded'], 2) : null,
            'pass_rate' => ($L['passed'] + $L['failed']) ? round(100 * $L['passed'] / ($L['passed'] + $L['failed']), 1) : null,
            'passed' => $L['passed'], 'failed' => $L['failed'], 'inc' => $L['inc'],
        ];
    }
    usort($levelList, fn($a, $b) => level_sort_key($a['level']) <=> level_sort_key($b['level']));
    usort($sections, fn($a, $b) => (level_sort_key($a['level']) <=> level_sort_key($b['level'])) ?: strcasecmp($a['label'], $b['label']));

    $st = $conn->prepare(
        "SELECT ss.section_id, ss.student_id, u.fullname, AVG(g.grade) AS avg_grade,
                SUM(CASE WHEN g.grade IS NOT NULL AND g.grade < $pass THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN g.id IS NOT NULL AND g.grade IS NULL THEN 1 ELSE 0 END) AS inc
         FROM section_students ss
         JOIN sections s ON s.id = ss.section_id AND (? = '' OR s.school_year = ?)
         JOIN users u ON u.user_id = ss.student_id AND u.status = 'active'
         JOIN grades g ON g.student_id = ss.student_id AND (? = '' OR g.school_year = ?) AND (? = '' OR g.term = ?)
         GROUP BY ss.section_id, ss.student_id, u.fullname"
    );
    $st->execute([$sy, $sy, $sy, $sy, $term, $term]);
    $labels = []; foreach ($secRows as $s) { $labels[(int)$s['id']] = sec_title($s); }
    $studs = []; $honors = 0;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['avg_grade'] === null) continue;
        $avg = round((float)$r['avg_grade'], 2);
        $honor = ((int)$r['failed'] === 0 && (int)$r['inc'] === 0) ? honor_label($avg) : null;
        if ($honor) $honors++;
        $studs[] = ['name' => $r['fullname'], 'id' => $r['student_id'], 'section' => $labels[(int)$r['section_id']] ?? '', 'avg' => $avg, 'honor' => $honor];
    }
    usort($studs, fn($a, $b) => ($b['avg'] <=> $a['avg']) ?: strcasecmp($a['name'], $b['name']));

    $pending = 0;
    try { $pending = $one("SELECT COUNT(*) FROM compliance_requests WHERE status = 'pending'"); } catch (Throwable $t) {}

    $periods = $conn->query("SELECT DISTINCT school_year, term FROM grades ORDER BY school_year DESC, term")->fetchAll(PDO::FETCH_ASSOC);
    $years = array_values(array_unique(array_column($periods, 'school_year')));
    $terms = array_values(array_unique(array_column($periods, 'term')));

    return [
        'sy' => $sy, 'term' => $term, 'generated' => date('H:i:s'),
        'kpi' => [
            'teachers_active' => $one("SELECT COUNT(*) FROM users WHERE role IN ($in) AND status = 'active'"),
            'teachers_total'  => $one("SELECT COUNT(*) FROM users WHERE role IN ($in)"),
            'students_active' => $one("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'"),
            'sections'        => count($sections),
            'pending'         => $pending,
            'grades'          => $gradesPosted,
            'honors'          => $honors,
        ],
        'levels' => $levelList, 'sections' => $sections, 'outcomes' => $outcomes,
        'top' => array_slice($studs, 0, 5),
        'students_ranked' => $studs,
        'years' => $years, 'terms' => $terms,
    ];
}