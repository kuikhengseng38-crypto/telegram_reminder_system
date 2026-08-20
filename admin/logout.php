<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
logout_admin();
header('Location: ' . app_url('admin/login.php'));
exit;
