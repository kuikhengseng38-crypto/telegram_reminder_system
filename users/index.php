<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$pageTitle = 'Telegram Users';
$pageKey = 'users';
$pageSubtitle = 'Recipients identified by Telegram chat_id';
$pageScripts = ['assets/js/users.js'];
$users = db()->query('SELECT id, name, chat_id, created_at FROM users ORDER BY id DESC')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>
<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Recipients</h2>
            <p class="muted mb-0">You can send to other people, not only yourself. They must first open <strong>@<?= e((string) telegram_config('bot_username')) ?></strong> and send <code>/start</code>. Use their numeric chat_id — not a phone number, username, or your own ID.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" type="button" id="btnImportBot" data-bot="<?= e((string) telegram_config('bot_username')) ?>">
                <i class="bi bi-download"></i> Import chats
            </button>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#userModal" id="btnAddUser">
                <i class="bi bi-plus-lg"></i> Add user
            </button>
        </div>
    </div>
    <div class="toolbar">
        <input class="form-control" id="userSearch" placeholder="Search name or chat_id">
    </div>
    <div class="table-responsive">
        <table class="table align-middle" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Chat ID</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row): ?>
                <tr>
                    <td><?= (int) $row['id'] ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><code><?= e($row['chat_id']) ?></code></td>
                    <td><?= e($row['created_at']) ?></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary btn-test-user" data-id="<?= (int) $row['id'] ?>">Test</button>
                        <button class="btn btn-sm btn-outline-secondary btn-edit-user"
                                data-id="<?= (int) $row['id'] ?>"
                                data-name="<?= e($row['name']) ?>"
                                data-chat="<?= e($row['chat_id']) ?>">Edit</button>
                        <button class="btn btn-sm btn-outline-danger btn-del-user" data-id="<?= (int) $row['id'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="userForm">
            <div class="modal-header">
                <h5 class="modal-title">Telegram user</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="userId">
                <label class="form-label">Display name</label>
                <input class="form-control mb-3" name="name" id="userName" required maxlength="150">
                <label class="form-label">Telegram chat_id</label>
                <input class="form-control" name="chat_id" id="userChatId" required maxlength="50" placeholder="e.g. 123456789">
                <div class="form-text">Numeric ID only. The person must have started a chat with the bot.</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
