<?php


ini_set("session.cookie_httponly", 1);
ini_set("session.use_strict_mode", 1);

session_start();
require __DIR__ . "/../db.php";   


if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: ../index.php");
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


define('PASSING_GRADE', 75);

$studentId = $_SESSION['user_id'];
$fullname  = $_SESSION['fullname'] ?? 'Student';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function csrf_ok(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function get_str(string $key): string {
    return (isset($_GET[$key]) && is_string($_GET[$key])) ? trim($_GET[$key]) : '';
}


function remark($grade): array {
    if ($grade === null || $grade === '') return ['INC', 'warn'];
    return ((float)$grade >= PASSING_GRADE) ? ['Passed', 'ok'] : ['Failed', 'bad'];
}

function fmt_grade($grade): string {
    return ($grade === null || $grade === '') ? '—' : number_format((float)$grade, 2);
}


$q = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE student_id = ? AND is_read = 0");
$q->execute([$studentId]);
$unreadCount = (int)$q->fetchColumn();