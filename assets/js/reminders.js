(function ($) {
    'use strict';

    function loadReminders() {
        var params = {
            action: 'list',
            q: $('input[name="q"]').val(),
            filter: $('select[name="filter"]').val()
        };
        APP.get('api/reminders.php', params).done(function (res) {
            if (!res.ok) return;
            var rows = res.data || [];
            if (!rows.length) {
                $('#reminderTableWrap').html('<div class="empty-state">No reminders match this search.</div>');
                return;
            }
            var html = '<div class="table-responsive"><table class="table align-middle"><thead><tr>' +
                '<th>Title</th><th>Scheduled</th><th>Messages</th><th>Users</th><th>Status</th><th></th>' +
                '</tr></thead><tbody>';
            rows.forEach(function (row) {
                html += '<tr>' +
                    '<td><a href="' + APP.base + '/reminders/view.php?id=' + row.id + '">' + APP.escape(row.title) + '</a></td>' +
                    '<td>' + APP.escape(row.scheduled_time) + '</td>' +
                    '<td>' + APP.escape(row.message_count) + '</td>' +
                    '<td>' + APP.escape(row.recipient_count) + '</td>' +
                    '<td>' + APP.statusBadge(row.status) + '</td>' +
                    '<td class="text-end text-nowrap">' +
                        (row.status === 'pending'
                            ? '<button class="btn btn-sm btn-primary btn-send-now" data-id="' + row.id + '">Send now</button> ' +
                              '<a class="btn btn-sm btn-outline-secondary" href="' + APP.base + '/reminders/form.php?id=' + row.id + '">Edit</a> '
                            : '') +
                        '<button class="btn btn-sm btn-outline-danger btn-del-reminder" data-id="' + row.id + '">Delete</button>' +
                    '</td></tr>';
            });
            html += '</tbody></table></div>';
            $('#reminderTableWrap').html(html);
        });
    }

    loadReminders();

    APP.api('api/reminders.php', { action: 'dispatch_due' }).done(function (res) {
        if (res && res.processed > 0) {
            APP.toast('Sent ' + res.processed + ' due reminder(s)', 'success');
            loadReminders();
        }
    });

    $(document).on('click', '.btn-send-now', function () {
        var btn = $(this).prop('disabled', true).text('Sending…');
        APP.api('api/reminders.php', { action: 'send_now', id: btn.data('id') }).done(function (res) {
            if (res.ok) {
                APP.toast('Sent to Telegram', 'success');
                loadReminders();
            } else {
                APP.toast(res.error || 'Send failed', 'error');
                btn.prop('disabled', false).text('Send now');
            }
        }).fail(function () {
            btn.prop('disabled', false).text('Send now');
        });
    });

    $(document).on('click', '.btn-del-reminder', function () {
        if (!confirm('Delete this reminder and its logs?')) return;
        APP.api('api/reminders.php', { action: 'delete', id: $(this).data('id') }).done(function (res) {
            if (res.ok) loadReminders();
            else APP.toast(res.error || 'Delete failed', 'error');
        });
    });
})(jQuery);
