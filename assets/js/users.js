(function ($) {
    'use strict';

    var modalEl = document.getElementById('userModal');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    $('#btnAddUser').on('click', function () {
        $('#userForm')[0].reset();
        $('#userId').val('');
    });

    $(document).on('click', '.btn-edit-user', function () {
        $('#userId').val($(this).data('id'));
        $('#userName').val($(this).data('name'));
        $('#userChatId').val($(this).data('chat'));
        modal.show();
    });

    $('#userForm').on('submit', function (e) {
        e.preventDefault();
        APP.api('api/users.php', {
            action: 'save',
            id: $('#userId').val(),
            name: $('#userName').val(),
            chat_id: $('#userChatId').val()
        }).done(function (res) {
            if (res.ok) {
                APP.toast('User saved', 'success');
                location.reload();
            } else {
                APP.toast(res.error || 'Save failed', 'error');
            }
        });
    });

    $(document).on('click', '.btn-del-user', function () {
        if (!confirm('Delete this Telegram user? Existing reminder assignments keep the old chat_id until edited.')) {
            return;
        }
        APP.api('api/users.php', { action: 'delete', id: $(this).data('id') }).done(function (res) {
            if (res.ok) location.reload();
            else APP.toast(res.error || 'Delete failed', 'error');
        });
    });

    $(document).on('click', '.btn-test-user', function () {
        var btn = $(this).prop('disabled', true);
        APP.api('api/users.php', { action: 'test_send', id: btn.data('id') }).done(function (res) {
            APP.toast(res.ok ? 'Test message sent' : (res.error || 'Send failed'), res.ok ? 'success' : 'error');
        }).always(function () {
            btn.prop('disabled', false);
        });
    });

    $('#btnImportBot').on('click', function () {
        var btn = $(this).prop('disabled', true);
        APP.api('api/users.php', { action: 'import_from_bot' }).done(function (res) {
            if (!res.ok) {
                APP.toast(res.error || 'Import failed', 'error');
                return;
            }
            if (!res.found) {
                APP.toast('Nobody has messaged the bot yet. Ask them to open @' + ($('#btnImportBot').data('bot') || 'YourBotUsername') + ' and send /start, then import again.', 'error');
                return;
            }
            APP.toast('Found ' + res.found + ', added ' + res.added + ', already saved ' + res.skipped, 'success');
            if (res.added > 0) {
                location.reload();
            }
        }).always(function () {
            btn.prop('disabled', false);
        });
    });

    $('#userSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('#usersTable tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });
})(jQuery);
