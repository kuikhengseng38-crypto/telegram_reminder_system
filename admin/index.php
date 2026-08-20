<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$pageTitle = 'Dashboard';
$pageKey = 'dashboard';
$pageSubtitle = 'Reminder delivery overview';

$pdo = db();
$stats = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'reminders' => (int) $pdo->query('SELECT COUNT(*) FROM reminders')->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'pending'")->fetchColumn(),
    'sent' => (int) $pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'sent'")->fetchColumn(),
    'failed' => (int) $pdo->query("SELECT COUNT(*) FROM reminders WHERE status IN ('failed','partial')")->fetchColumn(),
    'today' => (int) $pdo->query("SELECT COUNT(*) FROM reminders WHERE DATE(scheduled_time) = CURDATE()")->fetchColumn(),
];
$upcoming = $pdo->query(
    "SELECT id, title, scheduled_time, status
     FROM reminders
     WHERE status IN ('pending','sending')
     ORDER BY scheduled_time ASC
     LIMIT 8"
)->fetchAll();
$recentLogs = $pdo->query(
    "SELECT ml.id, ml.reminder_id, ml.chat_id, ml.status, ml.sent_time, ml.message_text, r.title
     FROM message_logs ml
     LEFT JOIN reminders r ON r.id = ml.reminder_id
     ORDER BY ml.id DESC
     LIMIT 8"
)->fetchAll();
$templates = reminder_templates();

require BASE_PATH . '/includes/header.php';
?>
<section class="stat-grid">
    <article class="stat-card"><span class="stat-icon teal"><i class="bi bi-people"></i></span><div><small>Telegram users</small><strong><?= (int) $stats['users'] ?></strong></div></article>
    <article class="stat-card"><span class="stat-icon blue"><i class="bi bi-alarm"></i></span><div><small>Total reminders</small><strong><?= (int) $stats['reminders'] ?></strong></div></article>
    <article class="stat-card"><span class="stat-icon amber"><i class="bi bi-hourglass-split"></i></span><div><small>Pending</small><strong><?= (int) $stats['pending'] ?></strong></div></article>
    <article class="stat-card"><span class="stat-icon green"><i class="bi bi-check2-circle"></i></span><div><small>Sent</small><strong><?= (int) $stats['sent'] ?></strong></div></article>
    <article class="stat-card"><span class="stat-icon red"><i class="bi bi-exclamation-octagon"></i></span><div><small>Failed / partial</small><strong><?= (int) $stats['failed'] ?></strong></div></article>
    <article class="stat-card"><span class="stat-icon violet"><i class="bi bi-calendar-day"></i></span><div><small>Today’s reminders</small><strong><?= (int) $stats['today'] ?></strong></div></article>
</section>

<div class="panel mb-4">
    <div class="panel-head">
        <div>
            <h2>Quick templates</h2>
            <p class="muted mb-0">Generate a bill or task reminder in one click.</p>
        </div>
    </div>
    <div class="template-grid">
        <?php foreach (array_slice($templates, 0, 4, true) as $key => $tpl): ?>
            <a class="template-card" href="<?= e(app_url('reminders/form.php?template=' . rawurlencode($key))) ?>">
                <span class="stat-icon <?= e($tpl['color']) ?>"><i class="bi <?= e($tpl['icon']) ?>"></i></span>
                <strong><?= e($tpl['name']) ?></strong>
                <small>Use this template</small>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-head">
                <h2>Upcoming jobs</h2>
                <a href="<?= e(app_url('reminders/form.php')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New reminder</a>
            </div>
            <?php if (!$upcoming): ?>
                <div class="empty-state">No pending reminders.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Title</th><th>Scheduled</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($upcoming as $row): ?>
                            <tr>
                                <td><a href="<?= e(app_url('reminders/view.php?id=' . (int) $row['id'])) ?>"><?= e($row['title']) ?></a></td>
                                <td><?= e($row['scheduled_time']) ?></td>
                                <td><span class="status-badge <?= e(status_badge_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-head">
                <h2>Latest deliveries</h2>
                <a href="<?= e(app_url('messages/')) ?>" class="btn btn-outline-secondary btn-sm">View logs</a>
            </div>
            <?php if (!$recentLogs): ?>
                <div class="empty-state">No messages have been sent yet.</div>
            <?php else: ?>
                <ul class="log-list">
                    <?php foreach ($recentLogs as $log): ?>
                        <li>
                            <span class="status-badge <?= e(status_badge_class($log['status'])) ?>"><?= e(status_label($log['status'])) ?></span>
                            <div>
                                <strong><?= e($log['title'] ?: ('Reminder #' . $log['reminder_id'])) ?></strong>
                                <small><?= e($log['chat_id']) ?> · <?= e((string) $log['sent_time']) ?></small>
                                <p><?= e(truncate_text((string) $log['message_text'], 90)) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
