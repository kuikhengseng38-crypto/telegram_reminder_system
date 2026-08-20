<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$id = (int) query('id');
$stmt = db()->prepare('SELECT * FROM reminders WHERE id = ?');
$stmt->execute([$id]);
$reminder = $stmt->fetch();
if (!$reminder) {
    redirect('reminders/');
}

$messages = db()->prepare('SELECT * FROM reminder_messages WHERE reminder_id = ? ORDER BY sort_order ASC, id ASC');
$messages->execute([$id]);
$messages = $messages->fetchAll();

$recipients = db()->prepare(
    'SELECT rr.chat_id, u.name
     FROM reminder_recipients rr
     LEFT JOIN users u ON u.chat_id = rr.chat_id
     WHERE rr.reminder_id = ?
     ORDER BY u.name ASC'
);
$recipients->execute([$id]);
$recipients = $recipients->fetchAll();

$logs = db()->prepare(
    'SELECT * FROM message_logs WHERE reminder_id = ? ORDER BY id ASC'
);
$logs->execute([$id]);
$logs = $logs->fetchAll();

$sentCount = 0;
$failCount = 0;
foreach ($logs as $log) {
    if ($log['status'] === 'sent') {
        $sentCount++;
    } else {
        $failCount++;
    }
}

$pageTitle = 'Reminder details';
$pageKey = 'reminders';
$pageSubtitle = $reminder['title'];

require BASE_PATH . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2><?= e($reminder['title']) ?></h2>
                    <p class="muted mb-0">Scheduled <?= e($reminder['scheduled_time']) ?></p>
                </div>
                <span class="status-badge <?= e(status_badge_class($reminder['status'])) ?>"><?= e(status_label($reminder['status'])) ?></span>
            </div>
            <dl class="meta-grid">
                <div><dt>Created</dt><dd><?= e($reminder['created_at']) ?></dd></div>
                <div><dt>Started</dt><dd><?= e($reminder['started_at'] ?: '—') ?></dd></div>
                <div><dt>Completed</dt><dd><?= e($reminder['completed_at'] ?: '—') ?></dd></div>
                <div><dt>Log summary</dt><dd><?= $sentCount ?> sent · <?= $failCount ?> failed</dd></div>
            </dl>
            <h3 class="h6 mt-4">Message sequence</h3>
            <ol class="seq-list">
                <?php foreach ($messages as $msg): ?>
                    <li>
                        <span class="seq"><?= (int) $msg['sort_order'] ?></span>
                        <pre><?= e($msg['message_text']) ?></pre>
                    </li>
                <?php endforeach; ?>
            </ol>
            <h3 class="h6 mt-4">Target users</h3>
            <ul class="chip-list">
                <?php foreach ($recipients as $r): ?>
                    <li>
                        <strong><?= e($r['name'] ?: 'Unknown') ?></strong>
                        <code><?= e($r['chat_id']) ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="form-actions">
                <?php if ($reminder['status'] === 'pending'): ?>
                    <a class="btn btn-outline-secondary" href="<?= e(app_url('reminders/form.php?id=' . $id)) ?>">Edit</a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary" href="<?= e(app_url('reminders/')) ?>">Back to list</a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="mb-3">Delivery log</h2>
            <?php if (!$logs): ?>
                <div class="empty-state">No sends yet. Cron will process this at the scheduled time.</div>
            <?php else: ?>
                <ul class="log-list compact">
                    <?php foreach ($logs as $log): ?>
                        <li>
                            <span class="status-badge <?= e(status_badge_class($log['status'])) ?>"><?= e(status_label($log['status'])) ?></span>
                            <div>
                                <strong><?= e($log['chat_id']) ?></strong>
                                <small><?= e((string) $log['sent_time']) ?></small>
                                <p><?= e(truncate_text((string) $log['message_text'], 80)) ?></p>
                                <?php if (!empty($log['error_message'])): ?>
                                    <p class="text-danger mb-0"><?= e($log['error_message']) ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
