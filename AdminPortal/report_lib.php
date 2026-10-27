<?php


function pct($v): string { return $v === null ? '—' : number_format((float)$v, 1) . '%'; }

function build_admin_report(PDO $conn, string $type, string $level, int $secId, string $sy, string $term): array {
    $out = ['title' => '', 'meta' => [], 'tables' => [], 'error' => null];
    $termTxt = $term === '' ? 'All terms' : $term;
    $syTxt   = $sy === '' ? 'All school years' : 'S.Y. ' . $sy;

    if ($type === 'overview') {
        $a = analytics($conn, $sy, $term);
        $out['title'] = 'School Overview Report';
        $out['meta']  = [$syTxt . ' · ' . $termTxt];

        $rows = [];
        foreach ($a['levels'] as $l) $rows[] = [$l['level'], $l['sections'], $l['students'], fmt_grade($l['avg']), pct($l['pass_rate']), $l['failed'], $l['inc']];
        $out['tables'][] = ['caption' => 'By grade level', 'head' => ['Grade level', 'Sections', 'Students', 'Average', 'Pass rate', 'Failed', 'INC'], 'rows' => $rows, 'num' => [1, 2, 3, 4, 5, 6]];

        $rows = [];
        foreach ($a['sections'] as $s) $rows[] = [$s['label'], $s['teacher'], $s['students'], fmt_grade($s['avg']), pct($s['pass_rate']), $s['failed'], $s['inc']];
        $out['tables'][] = ['caption' => 'By section', 'head' => ['Grade & Section', 'Teacher', 'Students', 'Average', 'Pass rate', 'Failed', 'INC'], 'rows' => $rows, 'num' => [2, 3, 4, 5, 6]];

        $rows = []; $i = 0;
        foreach (array_slice($a['students_ranked'], 0, 10) as $s) { $i++; $rows[] = [$i, $s['id'], $s['name'], $s['section'], fmt_grade($s['avg']), $s['honor'] ?: '']; }
        $out['tables'][] = ['caption' => 'Top 10 students', 'head' => ['#', 'Student ID', 'Student', 'Grade & Section', 'Average', 'Honors'], 'rows' => $rows, 'num' => [0, 4]];
        return $out;
    }

    if ($type === 'level') {
        if (!in_array($level, GRADE_LEVELS, true)) { $out['error'] = 'Choose a grade level.'; return $out; }
        $lbl = level_label($level);
        $a = analytics($conn, $sy, $term);
        $out['title'] = $lbl . ' Report';
        $out['meta']  = [$syTxt . ' · ' . $termTxt];

        $rows = [];
        foreach ($a['sections'] as $s) if ($s['level'] === $lbl) $rows[] = [$s['label'], $s['teacher'], $s['students'], fmt_grade($s['avg']), pct($s['pass_rate']), $s['failed'], $s['inc']];
        $out['tables'][] = ['caption' => 'Sections', 'head' => ['Grade & Section', 'Teacher', 'Students', 'Average', 'Pass rate', 'Failed', 'INC'], 'rows' => $rows, 'num' => [2, 3, 4, 5, 6]];

        $st = $conn->prepare("SELECT id, name, grade_level FROM sections WHERE grade_level = ? AND (? = '' OR school_year = ?)");
        $st->execute([$level, $sy, $sy]);
        $all = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $s) {
            foreach (section_ranking($conn, (int)$s['id'], $sy, $term) as $r) { $r['section'] = sec_title($s); $all[] = $r; }
        }
        usort($all, fn($x, $y) => ($x['avg'] === null) <=> ($y['avg'] === null) ?: ($y['avg'] <=> $x['avg']) ?: strcasecmp($x['fullname'], $y['fullname']));
        $all = rank_rows($all);
        $rows = [];
        foreach ($all as $r) $rows[] = [$r['rank'] ?? '—', $r['student_id'], $r['fullname'], $r['section'], fmt_grade($r['avg']), row_remarks($r)];
        $out['tables'][] = ['caption' => 'Student ranking across ' . $lbl, 'head' => ['#', 'Student ID', 'Student', 'Grade & Section', 'Average', 'Remarks'], 'rows' => $rows, 'num' => [0, 4]];
        return $out;
    }

    if ($type === 'section') {
        $sec = get_section($conn, $secId);
        if (!$sec) { $out['error'] = 'Choose a section.'; return $out; }
        if (!valid_sy($sy)) { $out['error'] = 'School year must look like 2026-2027.'; return $out; }
        if (!in_array($term, TERMS, true)) $term = TERMS[0];

        [$subjects, $rows] = build_report($conn, (int)$sec['id'], $sy, $term);
        $out['title'] = sec_title($sec) . ' — Grade Report';
        $out['meta']  = ['S.Y. ' . $sy . ' · ' . $term, 'Teacher: ' . ($sec['teacher'] ?: '—')];

        $head = ['#', 'Student ID', 'Student'];
        foreach ($subjects as $s) $head[] = $s['subject_code'];
        $head[] = 'Average'; $head[] = 'Remarks';
        $tbl = []; 
        foreach ($rows as $r) {
            $line = [$r['rank'] ?? '—', $r['student_id'], $r['fullname']];
            foreach ($subjects as $s) {
                $has = array_key_exists($s['subject_code'], $r['grades']);
                $line[] = $has ? ($r['grades'][$s['subject_code']] === null ? 'INC' : fmt_grade($r['grades'][$s['subject_code']])) : '';
            }
            $line[] = fmt_grade($r['avg']); $line[] = row_remarks($r);
            $tbl[] = $line;
        }
        $num = array_merge([0], range(3, 3 + count($subjects)));
        $out['tables'][] = ['caption' => 'Grades', 'head' => $head, 'rows' => $tbl, 'num' => $num, 'note' => $subjects ? implode('; ', array_map(fn($s) => $s['subject_code'] . ' = ' . $s['subject_name'], $subjects)) : ''];
        if (!$subjects) $out['meta'][] = 'No grades posted for this section and term yet.';
        return $out;
    }

    $out['error'] = 'Choose a report type.';
    return $out;
}

function report_params(): array {
    $type  = get_str('type');
    if (!in_array($type, ['overview', 'level', 'section'], true)) $type = '';
    $level = get_str('level');
    $secId = (int)($_GET['section'] ?? 0);
    $sy    = get_str('sy');
    if ($sy !== '' && !valid_sy($sy)) $sy = '';
    $term  = get_str('term');
    if ($term !== '' && !in_array($term, TERMS, true)) $term = '';
    return [$type, $level, $secId, $sy, $term];
}