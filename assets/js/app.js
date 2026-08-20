(function ($) {
    'use strict';

    window.APP = window.APP || {};

    function tickClock() {
        var el = document.getElementById('liveClock');
        if (!el) return;
        var now = new Date();
        el.textContent = now.toLocaleString('en-GB', {
            year: 'numeric', month: 'short', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
    tickClock();
    setInterval(tickClock, 1000);

    $('#sidebarToggle').on('click', function () {
        $('#appSidebar').toggleClass('open');
        $('#sidebarBackdrop').toggleClass('show');
    });
    $('#sidebarBackdrop').on('click', function () {
        $('#appSidebar').removeClass('open');
        $('#sidebarBackdrop').removeClass('show');
    });

    function toast(message, type) {
        var wrap = $('.toast-wrap');
        if (!wrap.length) {
            wrap = $('<div class="toast-wrap"></div>').appendTo('body');
        }
        var item = $('<div class="app-toast"></div>').addClass(type || '').text(message);
        wrap.append(item);
        setTimeout(function () { item.fadeOut(200, function () { item.remove(); }); }, 3200);
    }

    window.APP.toast = toast;

    window.APP.api = function (url, data) {
        data = data || {};
        data._csrf = APP.csrf;
        return $.ajax({
            url: APP.base + '/' + url.replace(/^\//, ''),
            method: 'POST',
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': APP.csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: JSON.stringify(data)
        }).fail(function (xhr) {
            var msg = 'Request failed';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            toast(msg, 'error');
        });
    };

    window.APP.get = function (url, data) {
        return $.ajax({
            url: APP.base + '/' + url.replace(/^\//, ''),
            method: 'GET',
            dataType: 'json',
            data: data || {},
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).fail(function (xhr) {
            var msg = 'Request failed';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            toast(msg, 'error');
        });
    };

    window.APP.escape = function (value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    window.APP.statusBadge = function (status) {
        var map = {
            pending: 'badge-pending',
            sending: 'badge-sending',
            sent: 'badge-sent',
            failed: 'badge-failed',
            partial: 'badge-partial'
        };
        var labels = {
            pending: 'Pending',
            sending: 'Sending',
            sent: 'Sent',
            failed: 'Failed',
            partial: 'Partially Sent'
        };
        var cls = map[status] || 'badge-pending';
        var label = labels[status] || status;
        return '<span class="status-badge ' + cls + '">' + APP.escape(label) + '</span>';
    };

    if (APP.csrf) {
        setTimeout(function () {
            APP.api('api/reminders.php', { action: 'dispatch_due' });
        }, 2000);
        setInterval(function () {
            APP.api('api/reminders.php', { action: 'dispatch_due' });
        }, 60000);
    }
})(jQuery);
