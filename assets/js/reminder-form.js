(function ($) {
    'use strict';

    function renumber() {
        $('#messageList .message-row').each(function (i) {
            $(this).find('.seq').text(i + 1);
        });
    }

    function addMessageRow(text) {
        var row = $(
            '<div class="message-row">' +
            '<div class="seq"></div>' +
            '<textarea class="form-control" name="messages[]" rows="3" maxlength="4096" required></textarea>' +
            '<button class="btn btn-outline-danger btn-remove" type="button"><i class="bi bi-trash"></i></button>' +
            '</div>'
        );
        row.find('textarea').val(text || '');
        $('#messageList').append(row);
        renumber();
    }

    function applyTemplate(key) {
        var data = {};
        try {
            data = JSON.parse($('#templateData').text() || '{}');
        } catch (e) {
            data = {};
        }
        var tpl = data[key];
        if (!tpl) return;
        $('#title').val(tpl.title);
        $('#messageList').empty();
        (tpl.messages || []).forEach(function (text) {
            addMessageRow(text);
        });
        if (!$('#messageList .message-row').length) {
            addMessageRow('');
        }
    }

    $('#addMessage').on('click', function () {
        addMessageRow('');
    });

    $('#templateSelect').on('change', function () {
        var key = $(this).val();
        if (!key) return;
        applyTemplate(key);
    });

    $(document).on('click', '.btn-remove', function () {
        if ($('#messageList .message-row').length <= 1) {
            APP.toast('At least one message is required', 'error');
            return;
        }
        $(this).closest('.message-row').remove();
        renumber();
    });

    $('#recipientFilter').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('.recipient-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    $('#reminderForm').on('submit', function (e) {
        e.preventDefault();
        if ($(this).data('locked') === 1 || $(this).data('locked') === '1') return;

        var messages = [];
        $('textarea[name="messages[]"]').each(function () {
            messages.push($(this).val());
        });
        var chatIds = [];
        $('input[name="chat_ids[]"]:checked').each(function () {
            chatIds.push($(this).val());
        });

        APP.api('api/reminders.php', {
            action: 'save',
            id: $('input[name="id"]').val(),
            title: $('#title').val(),
            scheduled_time: $('#scheduled_time').val(),
            messages: messages,
            chat_ids: chatIds
        }).done(function (res) {
            if (res.ok) {
                APP.toast('Reminder saved', 'success');
                window.location.href = APP.base + '/reminders/view.php?id=' + res.id;
            } else {
                APP.toast(res.error || 'Save failed', 'error');
            }
        });
    });
})(jQuery);
