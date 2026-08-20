<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$id = (int) query('id');
$templateKey = trim((string) query('template'));
$template = $templateKey !== '' ? reminder_template($templateKey) : null;

$reminder = null;
$messages = [['message_text' => '', 'sort_order' => 1]];
$selectedChats = [];
$titleValue = '';
$scheduledValue = date('Y-m-d\TH:i', strtotime('+1 hour'));

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM reminders WHERE id = ?');
    $stmt->execute([$id]);
    $reminder = $stmt->fetch();
    if (!$reminder) {
        redirect('reminders/');
    }
    $ms = db()->prepare('SELECT message_text, sort_order FROM reminder_messages WHERE reminder_id = ? ORDER BY sort_order ASC, id ASC');
    $ms->execute([$id]);
    $messages = $ms->fetchAll() ?: $messages;
    $titleValue = (string) $reminder['title'];
    $scheduledValue = date('Y-m-d\TH:i', strtotime($reminder['scheduled_time']));

    $rs = db()->prepare('SELECT chat_id FROM reminder_recipients WHERE reminder_id = ?');
    $rs->execute([$id]);
    $selectedChats = array_column($rs->fetchAll(), 'chat_id');
} elseif ($template) {
    $titleValue = (string) $template['title'];
    $messages = [];
    foreach ($template['messages'] as $i => $text) {
        $messages[] = ['message_text' => $text, 'sort_order' => $i + 1];
    }
}

$users = db()->query('SELECT id, name, chat_id FROM users ORDER BY name ASC')->fetchAll();
$locked = $reminder && !in_array($reminder['status'], ['pending'], true);
$templates = reminder_templates();

$pageTitle = $reminder ? 'Edit reminder' : ($template ? $template['name'] : 'Create reminder');
$pageKey = 'reminders';
$pageSubtitle = 'Title, schedule, message sequence, and recipients';
$pageScripts = ['assets/js/reminder-form.js'];

require BASE_PATH . '/includes/header.php';
?>
<form id="reminderForm" class="reminder-form" data-locked="<?= $locked ? '1' : '0' ?>">
    <input type="hidden" name="id" value="<?= $reminder ? (int) $reminder['id'] : 0 ?>">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="panel">
                <h2 class="mb-3">Reminder details</h2>
                <?php if ($locked): ?>
                    <div class="alert alert-warning">This reminder has already been processed and cannot be edited.</div>
                <?php endif; ?>
                <?php if (!$locked): ?>
                    <label class="form-label">Load a template</label>
                    <select class="form-select mb-3" id="templateSelect">
                        <option value="">Custom reminder</option>
                        <?php foreach ($templates as $key => $tpl): ?>
                            <option value="<?= e($key) ?>" <?= $templateKey === $key ? 'selected' : '' ?>><?= e($tpl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <label class="form-label">Title</label>
                <input class="form-control mb-3" name="title" id="title" required maxlength="255"
                       value="<?= e($titleValue) ?>" <?= $locked ? 'disabled' : '' ?>>
                <label class="form-label">Scheduled date &amp; time</label>
                <input class="form-control mb-4" type="datetime-local" name="scheduled_time" id="scheduled_time" required
                       value="<?= e($scheduledValue) ?>"
                       <?= $locked ? 'disabled' : '' ?>>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">Message sequence</h3>
                    <?php if (!$locked): ?>
                    <button class="btn btn-sm btn-outline-primary" type="button" id="addMessage">
                        <i class="bi bi-plus-lg"></i> Add message
                    </button>
                    <?php endif; ?>
                </div>
                <p class="muted">Messages are sent in this order, with a short delay between each send.</p>
                <div id="messageList">
                    <?php foreach ($messages as $i => $msg): ?>
                        <div class="message-row">
                            <div class="seq"><?= (int) ($msg['sort_order'] ?: ($i + 1)) ?></div>
                            <textarea class="form-control" name="messages[]" rows="3" maxlength="4096" required <?= $locked ? 'disabled' : '' ?>><?= e($msg['message_text']) ?></textarea>
                            <?php if (!$locked): ?>
                            <button class="btn btn-outline-danger btn-remove" type="button" title="Remove"><i class="bi bi-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="panel">
                <h2 class="mb-3">Target users</h2>
                <input class="form-control mb-3" id="recipientFilter" placeholder="Filter users…" <?= $locked ? 'disabled' : '' ?>>
                <div class="recipient-list">
                    <?php if (!$users): ?>
                        <p class="muted">No users yet. <a href="<?= e(app_url('users/')) ?>">Add a Telegram user</a> first.</p>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <label class="recipient-item">
                                <input type="checkbox" name="chat_ids[]" value="<?= e($user['chat_id']) ?>"
                                    <?= in_array($user['chat_id'], $selectedChats, true) ? 'checked' : '' ?>
                                    <?= $locked ? 'disabled' : '' ?>>
                                <span>
                                    <strong><?= e($user['name']) ?></strong>
                                    <small><?= e($user['chat_id']) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="form-actions">
                    <a class="btn btn-outline-secondary" href="<?= e(app_url('reminders/')) ?>">Cancel</a>
                    <?php if (!$locked): ?>
                        <button class="btn btn-primary" type="submit">Save reminder</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>
<script type="application/json" id="templateData"><?= json_encode($templates, JSON_UNESCAPED_UNICODE) ?></script>
<?php require BASE_PATH . '/includes/footer.php'; ?>
