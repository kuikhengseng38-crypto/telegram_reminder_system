<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';

if (is_logged_in()) {
    redirect('admin/');
}

$notice = '';
$error = '';

if (is_post()) {
    csrf_verify_request();
    $identity = trim((string) post('identity'));
    if ($identity === '') {
        $error = 'Please enter your username or email.';
    } else {
        create_reset_token($identity);
        $notice = 'If the account exists, a reset link has been sent to the registered email. On hosts without mail, the link is also written to logs/password_reset.log.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password · <?= e((string) app_config('app_short')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-shell single">
        <section class="auth-card-wrap">
            <div class="auth-card">
                <h2>Forgot password</h2>
                <p class="muted">Enter your admin username or email. A one-hour reset link will be generated.</p>
                <?php if ($notice !== ''): ?>
                    <div class="alert alert-success"><?= e($notice) ?></div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <label class="form-label" for="identity">Username or email</label>
                    <input class="form-control mb-3" id="identity" name="identity" required
                           value="<?= e((string) post('identity')) ?>">
                    <button class="btn btn-primary w-100" type="submit">Send reset link</button>
                </form>
                <a class="forgot-link" href="<?= e(app_url('admin/login.php')) ?>">Back to login</a>
            </div>
        </section>
    </div>
</body>
</html>
