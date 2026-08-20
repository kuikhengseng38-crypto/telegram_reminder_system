<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $pageKey */
$pageTitle = $pageTitle ?? 'Dashboard';
$pageKey = $pageKey ?? 'dashboard';
$admin = current_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> · <?= e((string) app_config('app_short')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="app-body">
<aside class="app-sidebar" id="appSidebar">
    <div class="brand">
        <span class="brand-mark"><i class="bi bi-send-fill"></i></span>
        <div>
            <strong><?= e((string) app_config('app_short')) ?></strong>
            <small>Telegram scheduler</small>
        </div>
    </div>
    <nav class="side-nav">
        <a class="nav-link <?= $pageKey === 'dashboard' ? 'active' : '' ?>" href="<?= e(app_url('admin/')) ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a class="nav-link <?= $pageKey === 'reminders' ? 'active' : '' ?>" href="<?= e(app_url('reminders/')) ?>">
            <i class="bi bi-alarm"></i> Reminders
        </a>
        <a class="nav-link <?= $pageKey === 'users' ? 'active' : '' ?>" href="<?= e(app_url('users/')) ?>">
            <i class="bi bi-people"></i> Telegram Users
        </a>
        <a class="nav-link <?= $pageKey === 'messages' ? 'active' : '' ?>" href="<?= e(app_url('messages/')) ?>">
            <i class="bi bi-chat-dots"></i> Message Logs
        </a>
        <a class="nav-link <?= $pageKey === 'admins' ? 'active' : '' ?>" href="<?= e(app_url('admin/admins.php')) ?>">
            <i class="bi bi-shield-lock"></i> Admins
        </a>
        <a class="nav-link <?= $pageKey === 'profile' ? 'active' : '' ?>" href="<?= e(app_url('admin/profile.php')) ?>">
            <i class="bi bi-person-gear"></i> Profile
        </a>
    </nav>
    <div class="side-foot">
        <div class="bot-chip">
            <i class="bi bi-robot"></i>
            @<?= e((string) telegram_config('bot_username')) ?>
        </div>
        <a class="nav-link logout" href="<?= e(app_url('admin/logout.php')) ?>">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</aside>
<div class="app-main">
    <header class="app-topbar">
        <button class="btn btn-ghost d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <h1><?= e($pageTitle) ?></h1>
            <p class="top-sub"><?= e($pageSubtitle ?? 'Manage scheduled Telegram reminders') ?></p>
        </div>
        <div class="top-meta">
            <span class="clock" id="liveClock"></span>
            <span class="admin-pill">
                <i class="bi bi-person-circle"></i>
                <?= e($admin['username'] ?? 'admin') ?>
            </span>
        </div>
    </header>
    <main class="app-content">
