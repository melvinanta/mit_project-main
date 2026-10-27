<?php
require __DIR__ . "/boot.php";
require __DIR__ . "/layout.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['read_all'])) {
        $conn->prepare("UPDATE teacher_notifications SET is_read = 1 WHERE teacher_id = ? AND is_read = 0")
             ->execute([$teacherId]);
    } elseif (isset($_POST['read_id'])) {
        $conn->prepare("UPDATE teacher_notifications SET is_read = 1 WHERE id = ? AND teacher_id = ?")
             ->execute([(int)$_POST['read_id'], $teacherId]);
    }
    header("Location: notifications.php");
    exit;
}

$st = $conn->prepare(
    "SELECT id, title, message, link, is_read, created_at
     FROM teacher_notifications WHERE teacher_id = ?
     ORDER BY created_at DESC, id DESC LIMIT 50"
);
$st->execute([$teacherId]);
$notes = $st->fetchAll(PDO::FETCH_ASSOC);


function safe_link(?string $l): ?string {
    return ($l && preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%.-]*)?$/', $l)) ? $l : null;
}

page_top('Notifications', 'notifications.php');
?>

        <div class="welcome my-4 d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h2>Notifications</h2>
                <p class="mb-0"><?= $unreadCount ?> unread &middot; from your students</p>
            </div>
            <?php if ($unreadCount > 0): ?>
            <form method="POST" class="m-0">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <button class="btn-white" name="read_all" value="1"><i class="bi bi-check2-all"></i>Mark all as read</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="glass section">
            <?php if (!$notes): ?>
                <div class="empty"><i class="bi bi-bell"></i><p class="mt-2 mb-0">You have no notifications.</p></div>
            <?php else: ?>
                <?php foreach ($notes as $nt): $link = safe_link($nt['link']); ?>
                <div class="note <?= $nt['is_read'] ? '' : 'unread' ?>">
                    <i class="bi <?= $nt['is_read'] ? 'bi-bell' : 'bi-bell-fill' ?>"></i>
                    <div class="flex-grow-1">
                        <strong><?= e($nt['title']) ?></strong>
                        <div><?= e($nt['message']) ?></div>
                        <small><?= e(date('M j, Y g:i A', strtotime($nt['created_at']))) ?></small>
                        <?php if ($link): ?><div class="mt-1"><a class="link-light fw-semibold" href="<?= e($link) ?>">View</a></div><?php endif; ?>
                    </div>
                    <?php if (!$nt['is_read']): ?>
                    <form method="POST" class="m-0">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <button class="mark-btn" name="read_id" value="<?= (int)$nt['id'] ?>" title="Mark as read"><i class="bi bi-check2"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

<?php page_bottom(); ?>