
/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_messages.js', function () {
    $(document).off('submit', '#sendMessageForm');
});

AJAX.registerOnload('server_messages.js', function () {

    /**
     * Ajax call for submit button. Sends a message to the database.
     */
    $(document).on('submit', '#sendMessageForm', function (event) {
        event.preventDefault();

        $form = $(this);

        var $msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);

        $.post(
            $form.attr('action'),
            $form.serialize(),
            function (data) {
                if (data !== 'undefined' && data.success == true) {
                    PMA_ajaxRemoveMessage(PMA_messages.strProcessingRequest);

                    // clear message from text area
                    $('#form_message').text('');
                    $('#form_receiver').text('');

                    // display success message
                    // TODO allow a PMA message to be sent without removing the rest of the screen's content
                    $('#message_sent_note').removeClass('.hide');

                } else if (data !== 'undefined' && data.success === false) {
                    PMA_ajaxShowMessage(data.error);
                } else {
                    PMA_ajaxShowMessage('Undefined error encountered when sending message.');
                }
            });
    });
});