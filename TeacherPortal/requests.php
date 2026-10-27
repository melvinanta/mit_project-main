<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$statuses = ['pending', 'approved', 'rejected'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash_set('danger', 'Your session expired. Please try again.');
        header("Location: requests.php");
        exit;
    }

    $id      = (int)($_POST['id'] ?? 0);
    $action  = post_str('action');
    $remarks = post_str('remarks');

    $st = $conn->prepare(
        "SELECT r.id, r.student_id, r.subject, r.status
         FROM compliance_requests r
         JOIN section_students ss ON ss.student_id = r.student_id
         JOIN sections s ON s.id = ss.section_id
         WHERE r.id = ? AND s.teacher_id = ? LIMIT 1"
    );
    $st->execute([$id, $teacherId]);
    $req = $st->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        flash_set('danger', 'Request not found.');
    } elseif ($req['status'] !== 'pending') {
        flash_set('warning', 'That request was already reviewed.');
    } elseif (!in_array($action, ['approve', 'reject'], true)) {
        flash_set('danger', 'Invalid action.');
    } elseif (mb_strlen($remarks) > 500) {
        flash_set('danger', 'Remarks can be up to 500 characters.');
    } elseif ($action === 'reject' && mb_strlen($remarks) < 3) {
        flash_set('danger', 'Please add a short reason when rejecting a request.');
    } else {
        $new = $action === 'approve' ? 'approved' : 'rejected';
        $conn->prepare("UPDATE compliance_requests SET status = ?, adviser_remarks = ? WHERE id = ? AND status = 'pending'")
             ->execute([$new, $remarks !== '' ? $remarks : null, $id]);

        notify_student(
            $conn, (string)$req['student_id'],
            'Compliance request ' . $new,
            'Your request for "' . $req['subject'] . '" was ' . $new . '.' . ($remarks !== '' ? ' Remarks: ' . $remarks : '')
        );
        flash_set('success', 'Request ' . $new . '. The student has been notified.');
    }
    header("Location: requests.php?status=" . urlencode(get_str('status') ?: 'pending'));
    exit;
}

$status = get_str('status');
if ($status !== '' && $status !== 'all' && !in_array($status, $statuses, true)) $status = 'pending';
if ($status === '') $status = 'pending';

$where  = "s.teacher_id = ?";
$params = [$teacherId];
if ($status !== 'all') { $where .= " AND r.status = ?"; $params[] = $status; }

$st = $conn->prepare(
    "SELECT r.id, r.student_id, u.fullname, s.name AS section_name, r.subject, r.type, r.details,
            r.status, r.adviser_remarks, r.created_at
     FROM compliance_requests r
     JOIN section_students ss ON ss.student_id = r.student_id
     JOIN sections s ON s.id = ss.section_id
     JOIN users u ON u.user_id = r.student_id
     WHERE $where ORDER BY r.created_at DESC, r.id DESC LIMIT 100"
);
$st->execute($params);
$requests = $st->fetchAll(PDO::FETCH_ASSOC);

page_top('Student Requests', 'requests.php');
?>

        <div class="welcome my-4">
            <h2>Student Requests</h2>
            <p>Review compliance requests from your students. They're notified when you decide.</p>
        </div>

        <div class="tabs mb-3">
            <?php foreach (array_merge($statuses, ['all']) as $t): ?>
                <a class="tab <?= $t === $status ? 'active' : '' ?>" href="requests.php?status=<?= e($t) ?>"><?= e(ucfirst($t)) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="glass section">
            <?php if (!$requests): ?>
                <div class="empty"><i class="bi bi-inbox"></i><p class="mt-2 mb-0">No <?= $status === 'all' ? '' : e($status) . ' ' ?>requests.</p></div>
            <?php else: ?>
                <?php foreach ($requests as $r): ?>
                <div class="req">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <div>
                            <strong><?= e($r['fullname']) ?></strong>
                            <span class="mono small opacity-75"><?= e($r['student_id']) ?></span>
                            <div class="small opacity-75"><?= e($r['section_name']) ?></div>
                        </div>
                        <span class="pill <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                    </div>
                    <div class="mt-2"><strong><?= e($r['subject']) ?></strong> &middot; <?= e($r['type']) ?>
                        <span class="small opacity-75">&middot; <?= e(date('M j, Y g:i A', strtotime($r['created_at']))) ?></span></div>
                    <div class="mt-2"><?= nl2br(e($r['details'])) ?></div>

                    <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" class="mt-3 glass-form">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <textarea name="remarks" class="form-control mb-2" rows="2" maxlength="500" placeholder="Remarks for the student (required when rejecting)"></textarea>
                            <div class="d-flex gap-2">
                                <button class="btn-white" name="action" value="approve"><i class="bi bi-check2-circle"></i>Approve</button>
                                <button class="btn-ghost" name="action" value="reject"><i class="bi bi-x-circle"></i>Reject</button>
                            </div>
                        </form>
                    <?php elseif (!empty($r['adviser_remarks'])): ?>
                        <div class="remarks"><i class="bi bi-chat-left-text me-1"></i><strong>Your remarks:</strong> <?= e($r['adviser_remarks']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

<?php page_bottom(); ?>