(function ($) {
    'use strict';

    function loadLogs() {
        APP.get('api/logs.php', {
            action: 'list',
            q: $('input[name="q"]').val(),
            filter: $('select[name="filter"]').val()
        }).done(function (res) {
            if (!res.ok) return;
            var rows = res.data || [];
            if (!rows.length) {
                $('#logsTableWrap').html('<div class="empty-state">No log records found.</div>');
                return;
            }
            var html = '<div class="table-responsive"><table class="table align-middle"><thead><tr>' +
                '<th>Reminder</th><th>User / chat_id</th><th>Message</th><th>Status</th><th>Sent time</th>' +
                '</tr></thead><tbody>';
            rows.forEach(function (row) {
                html += '<tr>' +
                    '<td><a href="' + APP.base + '/reminders/view.php?id=' + row.reminder_id + '">' +
                    APP.escape(row.title || ('#' + row.reminder_id)) + '</a></td>' +
                    '<td>' + APP.escape(row.user_name || '—') + '<br><code>' + APP.escape(row.chat_id) + '</code></td>' +
                    '<td>' + APP.escape((row.message_text || '').slice(0, 90)) + '</td>' +
                    '<td>' + APP.statusBadge(row.status) +
                    (row.error_message ? '<div class="text-danger small">' + APP.escape(row.error_message) + '</div>' : '') +
                    '</td>' +
                    '<td>' + APP.escape(row.sent_time || '—') + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table></div>';
            $('#logsTableWrap').html(html);
        });
    }

    loadLogs();
})(jQuery);
