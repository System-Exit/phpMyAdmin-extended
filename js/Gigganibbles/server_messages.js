
/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_messages.js', function () {
    $(document).off('submit', '#sendMessageForm');
});

AJAX.registerOnload('server_messages.js', function () {
    $(document).on('submit', '#sendMessageForm', function (event) {
        event.preventDefault();

        $form = $(this);

        PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (data !== 'undefined' && data.success === true) {
                PMA_ajaxShowMessage(data.message);

                // clear message
                $('#message').text('');
            } else if (data === 'undefined') {
                PMA_ajaxShowMessage()
            }
        });
    });
});