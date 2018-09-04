// variables
var ser_mes_MESSAGE_GET_LIMIT = 10;
var ser_mes_ERROR_UNEXPECTED = 31800;
var ser_mes_ERROR_SERVER_GET = 31900;
var ser_mes_ERROR_SERVER_GET_FATAL = 32000;
var ser_mes_ERROR_SERVER_SEEN = 33000;
var ser_mes_ERROR_SERVER_DISPLAYED = 33100;
var ser_mes_loadedMessages = [];

// set debug for now
// AJAX._debug = true;

// all functions

function displayMessageError(id, reason) {
    reason = reason || 'Unexpected error occurred.';
    var message = 'Error ' + id + ' : ' + reason;
    PMA_ajaxShowMessage(message, false, 'error');
}

function dateToSqlDate(datetime, local) {
    if (typeof local === 'undefined' || local === null) {
        local = false;
    }
    var date;
    if (datetime === -1) {
        date = new Date();
    } else {
        date = new Date(datetime);
    }
    if (local) {
        return date.getFullYear() + '-' +
            ('00' + (date.getMonth() + 1)).slice(-2) + '-' +
            ('00' + date.getDate()).slice(-2) + ' ' +
            ('00' + date.getHours()).slice(-2) + ':' +
            ('00' + date.getMinutes()).slice(-2) + ':' +
            ('00' + date.getSeconds()).slice(-2);
    } else {
        return date.getUTCFullYear() + '-' +
            ('00' + (date.getUTCMonth() + 1)).slice(-2) + '-' +
            ('00' + date.getUTCDate()).slice(-2) + ' ' +
            ('00' + date.getUTCHours()).slice(-2) + ':' +
            ('00' + date.getUTCMinutes()).slice(-2) + ':' +
            ('00' + date.getUTCSeconds()).slice(-2);
    }
}

/**
 * Pass a Date object or null, and a UTC date is returned.
 * @param datetime
 * @returns {Date} UTC date
 */
function getUTCDate(datetime) {
    if (typeof datetime === 'undefined' || datetime === null) {
        datetime = new Date();
    }
    return new Date(Date.UTC(
        datetime.getUTCFullYear(),
        datetime.getUTCMonth(),
        datetime.getUTCDay(),
        datetime.getUTCHours(),
        datetime.getUTCMinutes(),
        datetime.getUTCSeconds()
    ));
}

function convertUTCToLocal(datetimeString) {
    var dateUTC = new Date(datetimeString);
    var offset = dateUTC.getTimezoneOffset();
    dateUTC.setMinutes(dateUTC.getMinutes() - offset);
    return dateToSqlDate(dateUTC, true);
}

/**
 * Converts a message object to HTML. Message should contain String
 * fields: id, message, sender, timestamp, seen. Otherwise, throws an
 * error.
 * @param message
 * @returns {string}
 */
function messageToHtml(message) {
    if (
        message.id === null ||
        message.message === null ||
        message.sender === null ||
        message.timestamp === null ||
        message.seen === null
    ) {
        throw "Error converting message '" + message + "' to HTML.";
    }
    return "<li class=\"message_body\">" +
        "<p class=\"message_sender\">Sender: <strong>" + message.sender + "</strong></p>" +
        "<p class=\"message_timestamp\">Time: <strong>" + message.timestamp + "</strong></p>" +
        (message.seen === false ? "<p class=\"message_new\">NEW</p>" : "") +
        "<p class=\"message_message\">" + message.message + "</p>\n" +
        "</li>";
}

function getOldestMessage() {
    if (ser_mes_loadedMessages.length === 0) {
        return -1;
    }
    var lowestDate = new Date();
    for (var message in ser_mes_loadedMessages) {
        var check = new Date(ser_mes_loadedMessages[message].timestamp);
        if (lowestDate > check) {
            lowestDate = check;
        }
    }
    return lowestDate;
}

function seenMessages() {
    var unseen = [];
    var addTo = 0;
    for (var m in ser_mes_loadedMessages) {
        var message = ser_mes_loadedMessages[m];
        if (message.seen === false) {
            unseen[addTo] = message.id;
            addTo++;
        }
    }
    if (unseen.length > 0) {
        var toServer = {
            mark_seen           : true,
            mark_seen_messages  : unseen,
            ajax_request        : true,
            ajax_page_request   : false
        };
        $.ajax({
            url: "server_messages.php",
            method: 'POST',
            dataType: 'json',
            data: toServer,
            success: function(response) {
                console.log(response);
                if (
                    typeof response.seen === 'undefined' ||
                    response.success === false
                ) {
                    var $errorMessage = "An error was encountered when attempting to mark messages as 'seen'.";
                    console.error($errorMessage);
                    displayMessageError(ser_mes_ERROR_SERVER_SEEN, $errorMessage);
                    return;
                }

                // if successful, silently set all unseen messages to seen
                if (response.seen.length > 0) {
                    for (var i = 0; i < response.seen.length; i++) {
                        ser_mes_loadedMessages[response.seen[i]].seen = true;
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error(textStatus + " : " + errorThrown);
                displayMessageError(ser_mes_ERROR_SERVER_SEEN, errorThrown);
            }
        });
    }
}

function getMessages(lastDate, limit) {

    var msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);

    var toServer = {
        get_messages : true,
        last_date : lastDate,
        limit : limit,
        ajax_request : true,
        ajax_page_request : false
    };
    $.ajax({
        url: "server_messages.php",
        method: 'POST',
        dataType: 'json',
        data: toServer,
        success: function(response) {
            PMA_ajaxRemoveMessage(msg);

            // fatal error getting data
            if (typeof response.data === 'undefined') {
                displayMessageError(
                    ser_mes_ERROR_SERVER_GET_FATAL,
                    "Fatal error getting messages from server."
                );
                return;
            }

            // no objects in data array, tell the user there are no messages!
            if (
                typeof response.data === 'object' &&
                Object.keys(response.data).length === 0
            ) {
                if ($("#no_messages").length > 0) {
                    $("#no_messages").remove();
                }
                var endMessage = '';
                if (ser_mes_loadedMessages.length > 0) {
                    endMessage = 'All your messages have been found.';
                } else {
                    endMessage = 'You have no messages.';
                }
                $("#message_container").append(
                    '<li id="no_messages" class="no_messages">' + endMessage + '</li>'
                );
                return;
            }

            // otherwise, print all messages to page
            var foundError = false;
            for (var e in response.data) {
                // add message object to array for later use.
                var message = {
                    'id'        : response.data[e].id,
                    'message'   : response.data[e].message,
                    'sender'    : response.data[e].sender,
                    'timestamp' : response.data[e].timestamp,
                    'seen'      : (response.data[e].seen == 'true' || response.data[e].seen == '1')
                };
                // change timestamp to current datetime
                message.timestamp = convertUTCToLocal(message.timestamp);

                // store message
                ser_mes_loadedMessages[response.data[e].id] = message;

                // if no messages are present, delete 'no message' element
                if ($("#no_messages").length > 0) {
                    $("#messageBox").html("<li id='message_container'></li>");
                }

                // add element to page
                try {
                    $("#message_container").append(messageToHtml(message));
                } catch(error) {
                    foundError = true;
                }
            }

            // check if the messages were displayed correctly.
            if (foundError === true) {
                displayMessageError(
                    ser_mes_ERROR_SERVER_DISPLAYED,
                    "An error was encountered when attempting to display the messages"
                );
            } else {
                // send message to server, telling it that the messages have
                // been received.
                seenMessages();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus + " : " + errorThrown);
            displayMessageError(ser_mes_ERROR_SERVER_GET, errorThrown);
        }
    });

}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('Giganibbles/server_messages.js', function () {
    $(document).off('click', '#message_form_send');
    $(document).off('click', '#updateMessages');
});

AJAX.registerOnload('Giganibbles/server_messages.js', function () {

    /**
     * Ajax call for submit button. Sends a message to the database.
     */
    $(document).on('click', '#message_form_send', function (event) {
        event.preventDefault();

        $form = $("#sendMessageForm");

        var msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);

        // prepare data -- there is an issue with the form requests being sent
        // as new page requests. This should remedy the problem.
        var preparedData = $form.serializeArray();
        preparedData.ajax_request = true;
        preparedData.ajax_page_request = false;

        $.post(
            $form.attr('action'),
            preparedData,
            function (data) {
                PMA_ajaxRemoveMessage(msg);
                if (data !== 'undefined' && data.success === true) {

                    // clear message from text area
                    $('#form_message').val('');
                    $('#form_receiver').val('');

                    // display success message
                    PMA_ajaxShowMessage(data.message, 5000);

                } else if (data !== 'undefined' && data.success === false) {
                    PMA_ajaxShowMessage(data.error, false);
                } else {
                    PMA_ajaxShowMessage('Undefined error encountered when sending message.', false, 'error');
                }
            });
    });

    /**
     * Whenever the "load more messages" button is clicked, get more messages.
     */
    $(document).on('click', '#updateMessages', function (event) {
        event.preventDefault();
        var lastDate = getOldestMessage();
        if (lastDate !== false) {
            getMessages(dateToSqlDate(lastDate), ser_mes_MESSAGE_GET_LIMIT);
        }
    });

    /**
     * Load messages on page load.
     */
    getMessages(dateToSqlDate(-1), ser_mes_MESSAGE_GET_LIMIT);

});