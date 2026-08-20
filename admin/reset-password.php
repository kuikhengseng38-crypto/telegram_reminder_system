<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';

if (is_logged_in()) {
    redirect('admin/');
}

$token = trim((string) query('token'));
$error = '';
$success = '';
$tokenOk = (bool) preg_match('/^[a-f0-9]{64}$/', $token);

if (!$tokenOk) {
    $error = 'This reset link is invalid.';
}

if ($tokenOk && is_post()) {
    csrf_verify_request();
    $password = (string) post('password');
    $confirm  = (string) post('confirm');
    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = consume_reset_token($token, $password);
        if (!empty($result['ok'])) {
            $success = 'Password updated. You can now sign in.';
        } else {
            $error = $result['error'] ?? 'Unable to reset password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password · <?= e((string) app_config('app_short')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-shell single">
        <section class="auth-card-wrap">
            <div class="auth-card">
                <h2>Set a new password</h2>
                <?php if ($success !== ''): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                    <a class="btn btn-primary w-100" href="<?= e(app_url('admin/login.php')) ?>">Go to login</a>
                <?php else: ?>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger py-2"><?= e($error) ?></div>
                    <?php endif; ?>
                    <?php if ($tokenOk && $success === ''): ?>
                    <form method="post" autocomplete="off">
                        <?= csrf_field() ?>
                        <label class="form-label" for="password">New password</label>
                        <div class="input-icon mb-3">
                            <i class="bi bi-lock"></i>
                            <input class="form-control" type="password" id="password" name="password" minlength="8" required>
                            <button class="toggle-pass" type="button" data-target="#password"><i class="bi bi-eye"></i></button>
                        </div>
                        <label class="form-label" for="confirm">Confirm password</label>
                        <div class="input-icon mb-4">
                            <i class="bi bi-lock"></i>
                            <input class="form-control" type="password" id="confirm" name="confirm" minlength="8" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="showPassCheck">
                            <label class="form-check-label" for="showPassCheck">Show password</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Update password</button>
                    </form>
                    <?php endif; ?>
                    <a class="forgot-link" href="<?= e(app_url('admin/login.php')) ?>">Back to login</a>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="<?= e(app_url('assets/js/login.js')) ?>"></script>
</body>
</html>
