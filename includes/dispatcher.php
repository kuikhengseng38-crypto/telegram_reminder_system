<?php
/**
 * Reminder dispatch: used by cron and by the admin "Send now" action.
 */

declare(strict_types=1);

function recover_stuck_jobs(): void
{
    $stmt = db()->prepare(
        "UPDATE reminders
         SET status = 'pending', started_at = NULL
         WHERE status = 'sending' AND started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt->execute();
}

function process_due_reminders(): int
{
    recover_stuck_jobs();
    $pdo = db();
    $due = $pdo->query(
        "SELECT id FROM reminders
         WHERE status = 'pending' AND scheduled_time <= NOW()
         ORDER BY scheduled_time ASC, id ASC
         LIMIT 20"
    )->fetchAll();

    $count = 0;
    foreach ($due as $row) {
        if (process_one_reminder((int) $row['id'])) {
            $count++;
        }
    }
    return $count;
}

function process_one_reminder(int $reminderId): bool
{
    $pdo = db();
    $claim = $pdo->prepare(
        "UPDATE reminders SET status = 'sending', started_at = ? WHERE id = ? AND status = 'pending'"
    );
    $claim->execute([now_dt(), $reminderId]);
    if ($claim->rowCount() === 0) {
        return false;
    }

    $msgStmt = $pdo->prepare(
        'SELECT id, message_text, sort_order FROM reminder_messages WHERE reminder_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $msgStmt->execute([$reminderId]);
    $messages = $msgStmt->fetchAll();

    $userStmt = $pdo->prepare('SELECT chat_id FROM reminder_recipients WHERE reminder_id = ?');
    $userStmt->execute([$reminderId]);
    $recipients = $userStmt->fetchAll();

    $logStmt = $pdo->prepare(
        'INSERT INTO message_logs (reminder_id, chat_id, message_text, status, sent_time, error_message)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $sent = 0;
    $failed = 0;

    if (!$messages || !$recipients) {
        $pdo->prepare("UPDATE reminders SET status = 'failed', completed_at = ? WHERE id = ?")
            ->execute([now_dt(), $reminderId]);
        write_log('cron', "Reminder #{$reminderId} failed: missing messages or recipients");
        return true;
    }

    write_log('cron', "Processing reminder #{$reminderId} users=" . count($recipients) . ' messages=' . count($messages));

    foreach ($recipients as $recipient) {
        $chatId = (string) $recipient['chat_id'];
        foreach ($messages as $message) {
            $text = (string) $message['message_text'];
            $result = sendTelegramMessage($chatId, $text);
            $status = $result['ok'] ? 'sent' : 'failed';
            $logStmt->execute([
                $reminderId,
                $chatId,
                $text,
                $status,
                now_dt(),
                $result['error'],
            ]);
            if ($result['ok']) {
                $sent++;
            } else {
                $failed++;
            }
            telegram_delay();
        }
    }

    if ($failed === 0) {
        $final = 'sent';
    } elseif ($sent === 0) {
        $final = 'failed';
    } else {
        $final = 'partial';
    }

    $pdo->prepare('UPDATE reminders SET status = ?, completed_at = ? WHERE id = ?')
        ->execute([$final, now_dt(), $reminderId]);

    write_log('cron', "Reminder #{$reminderId} finished status={$final} sent={$sent} failed={$failed}");
    return true;
}
