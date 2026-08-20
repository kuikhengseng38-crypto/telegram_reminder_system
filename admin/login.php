<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';

if (is_logged_in()) {
    redirect('admin/');
}

$error = '';
$prefillUser = '';
$prefillPass = '';
if (is_post()) {
    csrf_verify_request();
    $result = attempt_login((string) post('username'), (string) post('password'));
    if (!empty($result['ok'])) {
        $next = $_SESSION['after_login'] ?? app_url('admin/');
        unset($_SESSION['after_login']);
        header('Location: ' . $next);
        exit;
    }
    $error = $result['error'] ?? 'Login failed.';
    $prefillUser = (string) post('username');
    $prefillPass = (string) post('password');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin Login · <?= e((string) app_config('app_short')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-shell">
        <section class="auth-hero">
            <div class="hero-content">
                <span class="brand-mark lg"><i class="bi bi-send-fill"></i></span>
                <h1><?= e((string) app_config('app_name')) ?></h1>
                <p>Schedule Telegram reminders, send sequenced messages, and track every delivery.</p>
                <ul class="hero-points">
                    <li><i class="bi bi-check2-circle"></i> Multiple messages per reminder</li>
                    <li><i class="bi bi-check2-circle"></i> Multiple recipients per reminder</li>
                    <li><i class="bi bi-check2-circle"></i> Cron-powered Bot API delivery</li>
                </ul>
            </div>
        </section>
        <section class="auth-card-wrap">
            <div class="auth-card">
                <h2>Admin sign in</h2>
                <p class="muted">Sign in with the account you created during install.</p>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="on" novalidate>
                    <?= csrf_field() ?>
                    <label class="form-label" for="username">Username</label>
                    <div class="input-icon mb-3">
                        <i class="bi bi-person"></i>
                        <input class="form-control" id="username" name="username" required maxlength="50"
                               value="<?= e($prefillUser) ?>" autocomplete="username">
                    </div>
                    <label class="form-label" for="password">Password</label>
                    <div class="input-icon mb-2">
                        <i class="bi bi-lock"></i>
                        <input class="form-control" type="password" id="password" name="password" required
                               value="<?= e($prefillPass) ?>" autocomplete="current-password">
                        <button class="toggle-pass" type="button" id="showPassword" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="showPassCheck">
                        <label class="form-check-label" for="showPassCheck">Show password</label>
                    </div>
                    <button class="btn btn-primary w-100 btn-lg" type="submit">Sign in</button>
                </form>
                <a class="forgot-link" href="<?= e(app_url('admin/forgot-password.php')) ?>">Forgot password?</a>
            </div>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="<?= e(app_url('assets/js/login.js')) ?>"></script>
</body>
</html>
