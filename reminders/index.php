<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$pageTitle = 'Reminders';
$pageKey = 'reminders';
$pageSubtitle = 'Create, schedule, and track Telegram reminders';
$pageScripts = ['assets/js/reminders.js'];

$filter = (string) query('filter', 'all');
$q = trim((string) query('q'));
$templates = reminder_templates();

require BASE_PATH . '/includes/header.php';
?>
<div class="panel mb-4">
    <div class="panel-head">
        <div>
            <h2>Quick templates</h2>
            <p class="muted mb-0">Click a template to generate a ready-to-send reminder. Then choose users and time.</p>
        </div>
    </div>
    <div class="template-grid">
        <?php foreach ($templates as $key => $tpl): ?>
            <a class="template-card" href="<?= e(app_url('reminders/form.php?template=' . rawurlencode($key))) ?>">
                <span class="stat-icon <?= e($tpl['color']) ?>"><i class="bi <?= e($tpl['icon']) ?>"></i></span>
                <strong><?= e($tpl['name']) ?></strong>
                <small><?= count($tpl['messages']) ?> messages</small>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>All reminders</h2>
        <a class="btn btn-primary" href="<?= e(app_url('reminders/form.php')) ?>">
            <i class="bi bi-plus-lg"></i> New reminder
        </a>
    </div>
    <p class="muted mb-3">Due reminders are sent automatically from PHP while this admin site is open. Keep the website running and leave an admin page in the browser.</p>
    <form class="filter-bar" method="get">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search title, chat ID, or message text">
        <select class="form-select" name="filter">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="sent" <?= $filter === 'sent' ? 'selected' : '' ?>>Sent</option>
            <option value="failed" <?= $filter === 'failed' ? 'selected' : '' ?>>Failed / partial</option>
            <option value="7days" <?= $filter === '7days' ? 'selected' : '' ?>>Past 7 days</option>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Search</button>
    </form>
    <div id="reminderTableWrap">
        <div class="empty-state">Loading…</div>
    </div>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
