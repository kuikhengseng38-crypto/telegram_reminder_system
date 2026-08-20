<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();

$pageTitle = 'Profile';
$pageKey = 'profile';
$pageSubtitle = 'Update your password and email';
$admin = current_admin();
$notice = '';
$error = '';

if (is_post()) {
    csrf_verify_request();
    $email = trim((string) post('email'));
    $current = (string) post('current_password');
    $new = (string) post('new_password');
    $confirm = (string) post('confirm_password');

    if (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $dup = db()->prepare('SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1');
        $dup->execute([$email, $admin['id']]);
        if ($dup->fetch()) {
            $error = 'That email is already in use.';
        }
    }

    if ($error === '' && $new !== '') {
        if (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $stmt = db()->prepare('SELECT password FROM admins WHERE id = ?');
            $stmt->execute([$admin['id']]);
            $hash = (string) $stmt->fetchColumn();
            if (!password_verify($current, $hash)) {
                $error = 'Current password is incorrect.';
            }
        }
    }

    if ($error === '') {
        if ($new !== '') {
            $upd = db()->prepare('UPDATE admins SET email = ?, password = ? WHERE id = ?');
            $upd->execute([$email, password_hash($new, PASSWORD_BCRYPT), $admin['id']]);
        } else {
            $upd = db()->prepare('UPDATE admins SET email = ? WHERE id = ?');
            $upd->execute([$email, $admin['id']]);
        }
        $notice = 'Profile updated.';
        $admin = current_admin(true);
    }
}

require BASE_PATH . '/includes/header.php';
?>
<div class="row">
    <div class="col-lg-6">
        <div class="panel">
            <h2 class="mb-4">Account details</h2>
            <?php if ($notice): ?><div class="alert alert-success"><?= e($notice) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <?= csrf_field() ?>
                <label class="form-label">Username</label>
                <input class="form-control mb-3" value="<?= e($admin['username']) ?>" disabled>
                <label class="form-label">Email</label>
                <input class="form-control mb-3" type="email" name="email" value="<?= e($admin['email']) ?>" required>
                <hr>
                <p class="muted">Leave password fields blank to keep the current password.</p>
                <label class="form-label">Current password</label>
                <input class="form-control mb-3" type="password" name="current_password">
                <label class="form-label">New password</label>
                <input class="form-control mb-3" type="password" name="new_password" minlength="8">
                <label class="form-label">Confirm new password</label>
                <input class="form-control mb-4" type="password" name="confirm_password" minlength="8">
                <button class="btn btn-primary" type="submit">Save changes</button>
            </form>
        </div>
    </div>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
