<?php
/**
 * EXAMPLE application configuration.
 * Copy this file to app.php and fill in your own values.
 * Do not commit app.php.
 */

return [
    'app_name'           => 'Telegram Reminder System',
    'app_short'          => 'KHS Reminder',
    'timezone'           => 'Asia/Kuala_Lumpur',
    'app_key'            => 'your_app_key',
    'cron_secret'        => 'YOUR_CRON_SECRET',
    'session_name'       => 'khs_reminder_sess',
    'session_lifetime'   => 14400,
    'reset_expiry'       => 3600,
    'login_max_attempts' => 5,
    'login_lock_minutes' => 15,
    'message_delay_ms'   => 1000,
    'telegram_timeout'   => 20,
];
