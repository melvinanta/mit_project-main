<?php

define('API_MODE', true);
require __DIR__ . "/boot.php";

$sy   = get_str('sy');
$term = get_str('term');
if ($sy !== '' && !valid_sy($sy)) $sy = '';
if ($term !== '' && !in_array($term, TERMS, true)) $term = '';

$data = analytics($conn, $sy, $term);
unset($data['students_ranked']);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);