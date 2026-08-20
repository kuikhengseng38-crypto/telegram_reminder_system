<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$pageTitle = 'Admins';
$pageKey = 'admins';
$pageSubtitle = 'Multiple administrator accounts';
$pageScripts = ['assets/js/admins.js'];
$admins = db()->query('SELECT id, username, email, is_active, last_login, created_at FROM admins ORDER BY id ASC')->fetchAll();

require BASE_PATH . '/includes/header.php';
?>
<div class="panel">
    <div class="panel-head">
        <h2>Administrator accounts</h2>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#adminModal" id="btnAddAdmin">
            <i class="bi bi-plus-lg"></i> Add admin
        </button>
    </div>
    <div class="table-responsive">
        <table class="table align-middle" id="adminsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($admins as $row): ?>
                <tr data-id="<?= (int) $row['id'] ?>">
                    <td><?= (int) $row['id'] ?></td>
                    <td><?= e($row['username']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td>
                        <span class="status-badge <?= (int) $row['is_active'] ? 'badge-sent' : 'badge-failed' ?>">
                            <?= (int) $row['is_active'] ? 'Active' : 'Disabled' ?>
                        </span>
                    </td>
                    <td><?= e($row['last_login'] ?: '—') ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary btn-edit-admin"
                                data-id="<?= (int) $row['id'] ?>"
                                data-username="<?= e($row['username']) ?>"
                                data-email="<?= e($row['email']) ?>"
                                data-active="<?= (int) $row['is_active'] ?>">Edit</button>
                        <?php if ((int) $row['id'] !== (int) current_admin()['id']): ?>
                            <button class="btn btn-sm btn-outline-danger btn-del-admin" data-id="<?= (int) $row['id'] ?>">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="adminModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="adminForm">
            <div class="modal-header">
                <h5 class="modal-title">Admin account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="adminId">
                <label class="form-label">Username</label>
                <input class="form-control mb-3" name="username" id="adminUsername" required maxlength="50">
                <label class="form-label">Email</label>
                <input class="form-control mb-3" type="email" name="email" id="adminEmail" required>
                <label class="form-label">Password <small class="text-muted" id="pwdHint">(required)</small></label>
                <input class="form-control mb-3" type="password" name="password" id="adminPassword" minlength="8">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="adminActive" checked>
                    <label class="form-check-label" for="adminActive">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
