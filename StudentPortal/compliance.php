<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";

$types = ['Incomplete grade (INC)', 'Missing requirement', 'Grade correction', 'Other'];

$errors  = [];
$subject = '';
$type    = $types[0];
$details = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors[] = "Your session expired. Please try again.";
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $type    = $_POST['type'] ?? '';
        $details = trim($_POST['details'] ?? '');

        if ($subject === '' || mb_strlen($subject) > 100) {
            $errors[] = "Enter the subject (up to 100 characters).";
        }
        if (!in_array($type, $types, true)) {
            $errors[] = "Choose a valid request type.";
        }
        if (mb_strlen($details) < 10 || mb_strlen($details) > 1000) {
            $errors[] = "Details must be between 10 and 1000 characters.";
        }

        if (!$errors) {
            $ins = $conn->prepare(
                "INSERT INTO compliance_requests (student_id, subject, type, details) VALUES (?, ?, ?, ?)"
            );
            $ins->execute([$studentId, $subject, $type, $details]);

            $note = $conn->prepare("INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)");
            $note->execute([
                $studentId,
                'Compliance request submitted',
                'Your request for "' . $subject . '" (' . $type . ') is now pending review.'
            ]);

            header("Location: compliance.php?sent=1");
            exit;
        }
    }
}

// This student's subjects, to suggest in the form
$sj = $conn->prepare("SELECT DISTINCT subject_name FROM grades WHERE student_id = ? ORDER BY subject_name");
$sj->execute([$studentId]);
$mySubjects = $sj->fetchAll(PDO::FETCH_COLUMN);

// This student's requests
$rq = $conn->prepare(
    "SELECT subject, type, details, status, adviser_remarks, created_at
     FROM compliance_requests WHERE student_id = ? ORDER BY created_at DESC, id DESC"
);
$rq->execute([$studentId]);
$requests = $rq->fetchAll(PDO::FETCH_ASSOC);

page_top('Compliance Request', 'compliance.php');
?>

        <div class="welcome my-4">
            <h2>Request Compliance</h2>
            <p>Submit a request to settle an incomplete grade, missing requirement or grade concern.</p>
        </div>

        <?php if (isset($_GET['sent'])): ?>
            <div class="alert alert-success glass-alert"><i class="bi bi-check-circle me-2"></i>Your request was submitted. You'll be notified when it's reviewed.</div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger glass-alert">
                <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-xl-5">
                <div class="glass section h-100 glass-form">
                    <h5>New Request</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                        <label for="subject">Subject</label>
                        <input id="subject" name="subject" class="form-control mb-3" list="subjectList"
                               maxlength="100" placeholder="e.g. Mathematics" value="<?= e($subject) ?>" required>
                        <datalist id="subjectList">
                            <?php foreach ($mySubjects as $ms): ?><option value="<?= e($ms) ?>"><?php endforeach; ?>
                        </datalist>

                        <label for="type">Request type</label>
                        <select id="type" name="type" class="form-select mb-3">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= e($t) ?>" <?= $t === $type ? 'selected' : '' ?>><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="details">Details</label>
                        <textarea id="details" name="details" class="form-control mb-3" rows="5"
                                  maxlength="1000" placeholder="Explain what you need and what you'll submit." required><?= e($details) ?></textarea>

                        <button class="btn-white w-100 justify-content-center"><i class="bi bi-send"></i>Submit Request</button>
                    </form>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="glass section h-100">
                    <h5>My Requests</h5>
                    <?php if (!$requests): ?>
                        <div class="empty"><i class="bi bi-file-earmark-check"></i><p class="mt-2 mb-0">You haven't submitted any requests yet.</p></div>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                        <div class="req">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <strong><?= e($r['subject']) ?></strong>
                                <span class="pill <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                            </div>
                            <div class="small opacity-75 mb-2"><?= e($r['type']) ?> &middot; <?= e(date('M j, Y', strtotime($r['created_at']))) ?></div>
                            <div><?= nl2br(e($r['details'])) ?></div>
                            <?php if (!empty($r['adviser_remarks'])): ?>
                                <div class="remarks"><i class="bi bi-chat-left-text me-1"></i><strong>Adviser:</strong> <?= e($r['adviser_remarks']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php page_bottom(); ?>