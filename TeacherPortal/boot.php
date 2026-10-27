<?php

ini_set("session.cookie_httponly", 1);
ini_set("session.use_strict_mode", 1);

session_start();
require __DIR__ . "/../db.php";  
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['teacher'], true)) {
    header("Location: ../index.php");
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


define('PASSING_GRADE', 75);
const TERMS = ['1st Semester', '2nd Semester', 'Summer'];

const HONOR_LEVELS = [[98, 'With Highest Honors'], [95, 'With High Honors'], [90, 'With Honors']];


$teacherId = $_SESSION['user_id'];
$fullname  = $_SESSION['fullname'] ?? 'Teacher';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function csrf_ok(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}
function get_str(string $k): string  { return (isset($_GET[$k])  && is_string($_GET[$k]))  ? trim($_GET[$k])  : ''; }
function post_str(string $k): string { return (isset($_POST[$k]) && is_string($_POST[$k])) ? trim($_POST[$k]) : ''; }

function remark($grade): array {
    if ($grade === null || $grade === '') return ['INC', 'warn'];
    return ((float)$grade >= PASSING_GRADE) ? ['Passed', 'ok'] : ['Failed', 'bad'];
}
function fmt_grade($grade): string {
    return ($grade === null || $grade === '') ? '—' : number_format((float)$grade, 2);
}

function default_school_year(): string {
    $y = (int)date('Y');
    return ((int)date('n') >= 6) ? "$y-" . ($y + 1) : ($y - 1) . "-$y";
}
function valid_sy(string $sy): bool { return (bool)preg_match('/^\d{4}-\d{4}$/', $sy); }

function gen_password(int $len = 8): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) { $out .= $chars[random_int(0, strlen($chars) - 1)]; }
    return $out;
}


function flash_set(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function flash_get(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}


function csv_safe($v) {
    $v = (string)$v;
    return (strlen($v) && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
}


function my_sections(PDO $conn, string $tid): array {
    $st = $conn->prepare(
        // "SELECT s.id, s.name, s.school_year, COUNT(ss.id) AS students
        "SELECT s.id, s.name, s.grade_level, s.school_year, COUNT(ss.id) AS students
         FROM sections s LEFT JOIN section_students ss ON ss.section_id = s.id
         WHERE s.teacher_id = ?
        --  GROUP BY s.id, s.name, s.school_year
        GROUP BY s.id, s.name, s.grade_level, s.school_year
         ORDER BY s.school_year DESC, s.name ASC"
    );
    $st->execute([$tid]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function owned_section(PDO $conn, int $sid, string $tid): ?array {
    if ($sid <= 0) return null;
    // $st = $conn->prepare("SELECT id, name, school_year FROM sections WHERE id = ? AND teacher_id = ? LIMIT 1");
    $st = $conn->prepare("SELECT id, name, grade_level, school_year FROM sections WHERE id = ? AND teacher_id = ? LIMIT 1");
    $st->execute([$sid, $tid]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function owned_student(PDO $conn, string $studentId, string $tid): ?array {
    $st = $conn->prepare(
        "SELECT u.user_id, u.fullname, u.status, ss.section_id, s.name AS section_name, s.school_year
         FROM users u
         JOIN section_students ss ON ss.student_id = u.user_id
         JOIN sections s ON s.id = ss.section_id
         WHERE u.user_id = ? AND u.role = 'student' AND s.teacher_id = ? LIMIT 1"
    );
    $st->execute([$studentId, $tid]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function notify_student(PDO $conn, string $studentId, string $title, string $message): void {
    $st = $conn->prepare("INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)");
    $st->execute([$studentId, $title, $message]);
}



function honor_label(float $avg): ?string {
    foreach (HONOR_LEVELS as $lvl) { if ($avg >= $lvl[0]) return $lvl[1]; }
    return null;
}


function section_ranking(PDO $conn, int $sectionId, string $sy = '', string $term = ''): array {
    $pass = (int)PASSING_GRADE;
    $st = $conn->prepare(
        "SELECT ss.student_id, u.fullname,
                AVG(g.grade) AS avg_grade,
                COUNT(g.id) AS total,
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
        $r['total']  = (int)$r['total'];
        $r['honor']  = ($r['avg'] !== null && $r['failed'] === 0 && $r['inc'] === 0) ? honor_label($r['avg']) : null;
        $r['rank']   = null;
        if ($r['avg'] !== null) { $ranked[] = $r; } else { $unranked[] = $r; }
    }
    usort($ranked, fn($a, $b) => ($b['avg'] <=> $a['avg']) ?: strcasecmp($a['fullname'], $b['fullname']));
    usort($unranked, fn($a, $b) => strcasecmp($a['fullname'], $b['fullname']));

    $lastRank = 0; $lastAvg = null;
    foreach ($ranked as $i => $r) {
        $rank = ($lastAvg !== null && $r['avg'] == $lastAvg) ? $lastRank : $i + 1;
        $ranked[$i]['rank'] = $rank;
        $lastRank = $rank; $lastAvg = $r['avg'];
    }
    return array_merge($ranked, $unranked);
}


function build_report(PDO $conn, int $sectionId, string $sy, string $term): array {
    $st = $conn->prepare(
        "SELECT DISTINCT g.subject_code, g.subject_name
         FROM grades g JOIN section_students ss ON ss.student_id = g.student_id
         WHERE ss.section_id = ? AND g.school_year = ? AND g.term = ?
         ORDER BY g.subject_code"
    );
    $st->execute([$sectionId, $sy, $term]);
    $subjects = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $conn->prepare(
        "SELECT g.student_id, g.subject_code, g.grade
         FROM grades g JOIN section_students ss ON ss.student_id = g.student_id
         WHERE ss.section_id = ? AND g.school_year = ? AND g.term = ?"
    );
    $st->execute([$sectionId, $sy, $term]);
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $g) {
        $map[(string)$g['student_id']][$g['subject_code']] = $g['grade'];
    }

    $rows = section_ranking($conn, $sectionId, $sy, $term);
    foreach ($rows as $i => $r) { $rows[$i]['grades'] = $map[(string)$r['student_id']] ?? []; }
    return [$subjects, $rows];
}

function row_remarks(array $r): string {
    if ($r['avg'] === null) return 'No grades';
    if ($r['honor'])        return $r['honor'];
    if ($r['failed'] > 0)   return 'Has failed subject(s)';
    if ($r['inc'] > 0)      return 'Has INC';
    return 'Passed';
}



$q = $conn->prepare("SELECT COUNT(*) FROM teacher_notifications WHERE teacher_id = ? AND is_read = 0");
$q->execute([$teacherId]);
$unreadCount = (int)$q->fetchColumn();

$q = $conn->prepare(
    "SELECT COUNT(*) FROM compliance_requests r
     JOIN section_students ss ON ss.student_id = r.student_id
     JOIN sections s ON s.id = ss.section_id
     WHERE s.teacher_id = ? AND r.status = 'pending'"
);
$q->execute([$teacherId]);
$pendingCount = (int)$q->fetchColumn();