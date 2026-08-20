(function ($) {
    'use strict';

    function setVisible(visible) {
        var type = visible ? 'text' : 'password';
        $('#password, #confirm').attr('type', type);
        $('.toggle-pass i').toggleClass('bi-eye', !visible).toggleClass('bi-eye-slash', visible);
        $('#showPassCheck').prop('checked', visible);
    }

    $('#showPassCheck').on('change', function () {
        setVisible(this.checked);
    });

    $(document).on('click', '.toggle-pass, #showPassword', function () {
        var visible = $('#password').attr('type') === 'password';
        setVisible(visible);
    });
})(jQuery);
