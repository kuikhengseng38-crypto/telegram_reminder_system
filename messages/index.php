<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$pageTitle = 'Message Logs';
$pageKey = 'messages';
$pageSubtitle = 'Per-message delivery history';
$pageScripts = ['assets/js/messages.js'];
$filter = (string) query('filter', 'all');
$q = trim((string) query('q'));

require BASE_PATH . '/includes/header.php';
?>
<div class="panel">
    <div class="panel-head">
        <h2>Message sending status</h2>
    </div>
    <form class="filter-bar" method="get" id="logFilter">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search title, chat ID, or message content">
        <select class="form-select" name="filter">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="sent" <?= $filter === 'sent' ? 'selected' : '' ?>>Sent</option>
            <option value="failed" <?= $filter === 'failed' ? 'selected' : '' ?>>Failed</option>
            <option value="7days" <?= $filter === '7days' ? 'selected' : '' ?>>Past 7 days</option>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Search</button>
    </form>
    <div id="logsTableWrap">
        <div class="empty-state">Loading…</div>
    </div>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
