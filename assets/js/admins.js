(function ($) {
    'use strict';

    var modalEl = document.getElementById('adminModal');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    $('#btnAddAdmin').on('click', function () {
        $('#adminForm')[0].reset();
        $('#adminId').val('');
        $('#adminActive').prop('checked', true);
        $('#pwdHint').text('(required)');
        $('#adminPassword').attr('required', true);
    });

    $(document).on('click', '.btn-edit-admin', function () {
        $('#adminId').val($(this).data('id'));
        $('#adminUsername').val($(this).data('username'));
        $('#adminEmail').val($(this).data('email'));
        $('#adminActive').prop('checked', String($(this).data('active')) === '1');
        $('#adminPassword').val('').removeAttr('required');
        $('#pwdHint').text('(leave blank to keep)');
        modal.show();
    });

    $('#adminForm').on('submit', function (e) {
        e.preventDefault();
        APP.api('api/admins.php', {
            action: 'save',
            id: $('#adminId').val(),
            username: $('#adminUsername').val(),
            email: $('#adminEmail').val(),
            password: $('#adminPassword').val(),
            is_active: $('#adminActive').is(':checked') ? 1 : 0
        }).done(function (res) {
            if (res.ok) {
                APP.toast('Admin saved', 'success');
                location.reload();
            } else {
                APP.toast(res.error || 'Save failed', 'error');
            }
        });
    });

    $(document).on('click', '.btn-del-admin', function () {
        if (!confirm('Delete this admin account?')) return;
        APP.api('api/admins.php', { action: 'delete', id: $(this).data('id') }).done(function (res) {
            if (res.ok) location.reload();
            else APP.toast(res.error || 'Delete failed', 'error');
        });
    });
})(jQuery);
