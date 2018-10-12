<?php
/**
 * Message controller for messages page
 *
 * @package PhpMyAdmin\Controllers\Giganibbles
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Giganibbles;

use phpDocumentor\Reflection\Types\Boolean;
use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Message;

/**
 * Class ServerMessagesController
 *
 * A controller which calls the twig for our messages page.
 *
 * @package PhpMyAdmin\Controllers\Giganibbles
 */
class ServerMessagesController extends Controller
{

    /**
     * Handles actions to perform on loading the page
     *
     * @return void
     */
    public function indexAction()
    {
        $response = $this->response;

        // pass to ajax function if page call is an ajax request
        // Note: error handling and validation is done in sendAction().
        if (
            $response->isAjax()
            && isset($_POST['form_send_check'])
        ) {
            $this->sendAction();
            return;
        }

        // if ajax request for marking messages as read, do so
        if (
            $response->isAjax()
            && isset($_POST['mark_seen'])
        ) {
            $this->markSeenAction();
            return;
        }

        // if ajax request for getting messages
        if (
            $response->isAjax()
            && isset($_POST['get_messages'])
        ) {
            // Get messages for current user
            $this->getMessages();
            return;
        }

        // upload script
        $header = $this->response->getHeader();
        $scriptsHeader = $header->getScripts();
        $scriptsHeader->addFile('Giganibbles/server_messages.js');

        // $response->setRequestStatus(true);
        $this->response->addHTML($this->template->render(
            'Giganibbles/server_messages',
            [
                // Passes messages array
                'messages' => []
            ]
        ));

        /**
         * Incrementation of message page visits if user is logged in
         */
        ServerUserStatsController::incrementPageView("messages");
    }

    /**
     * Stores a message in the database. This message has three params:
     *
     *  - 'sender'      : string (must match current user)
     *  - 'receiver'    : string (must be in mysql.user)
     *  - 'message'     : string (not empty)
     *
     * @return void
     */
    public function sendAction()
    {
        global $cfg;
        $response = $this->response;

        // checks if all data is set. If not, throws an error.
        if (empty($_POST['form_receiver'])
            || empty($_POST['form_message'])
        ) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Error: Message and receiver need to be filled out.'));
            $response->addJSON('message', $message_out);
            return;
        }

        $sender = $cfg['Server']['user'];
        $receiver = $this->dbi->escapeString($_POST['form_receiver']);
        $message = $this->dbi->escapeString($_POST['form_message']);

        // checks if user is current
        if (empty($sender)) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Error: there was an issue with the current user. Please try logging out and in again.'));
            $response->addJSON('message', $message_out);
            return;
        }

        // check if receiver exists
        $relation = new Relation();
        $receiver_result = $relation->queryAsControlUser(
            "SELECT User FROM mysql.user WHERE User LIKE '$receiver';",
            true);
        if (empty($receiver_result) || $receiver_result->num_rows == 0) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Error: the receiver is not a valid user.'));
            $response->addJSON('message', $message_out);
            return;
        }

        // check if message is empty
        if (empty($message)) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Error: the message has not been set.'));
            $response->addJSON('message', $message_out);
            return;
        }

        // create new message
        $success = $this->sendMessage($sender, $receiver, $message);

        if ($success === false) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Error: Could not send the message.'));
            $response->addJSON('message', $message_out);
        } else {
            $response->setRequestStatus(true);
            $message_out = Message::success(__('The message has been successfully sent.'));
            $response->addJSON('message', $message_out);
        }

        return;
    }

    /**
     * Marks all messages for current user as seen.
     *
     * @return void
     */
    public function markSeenAction()
    {
        global $cfg;
        $response = $this->response;
        $relation = new Relation();

        $user = $cfg['Server']['user'];
        $toUpdate = $_POST['mark_seen_messages'];
        $updated = [];
        $queueError = false;

        // iterate through ids and mark as seen
        foreach ($toUpdate as $id) {
            // Sets all retrieved messages as read
            $query = "UPDATE phpmyadmin.pma__messages msg "
                     . "SET msg.seen = true, msg.timestamp = msg.timestamp "
                     . "WHERE msg.receiver LIKE '$user' "
                     . "AND msg.seen = false "
                     . "AND msg.id = $id;";
            $success = $relation->queryAsControlUser($query);
            if ($success !== false) {
                array_push($updated, $id);
            } else {
                $queueError = true;
            }
        }

        if($queueError === true) {
            $messageOut = Message::error(
                __("New messages could not be marked as 'seen'.")
            );
            $response->setRequestStatus(false);
            $response->addJSON('message', $messageOut);
        } else {
            $response->setRequestStatus(true);
            $response->addJSON('seen', $updated);
        }
        return;
    }

    /**
     *  Queries and returns the messages that the specified user has received
     *
     * @return string array of strings of data found from query, false if fail
     */
    public function getMessages()
    {
        global $cfg;
        $relation = new Relation();

        $user = $cfg['Server']['user'];
        if (empty($_POST['last_date'])) {
            $lastDate = date('Y-m-d H:i:s');
        } else {
            try {
                $lastDate = \DateTime::createFromFormat('Y-m-d H:i:s', $_POST['last_date']);
            } catch (\Exception $e) {
                $lastDate = date('Y-m-d H:i:s');
            }
        }
        $limit = !empty($_POST['limit']) ? intval($_POST['limit']) : -1;

        // Query for all messages sent to current user
        $query = "SELECT msg.id, msg.message, msg.sender, msg.timestamp, msg.seen "
            . "FROM phpmyadmin.pma__messages msg "
            . "WHERE msg.receiver LIKE '$user' "
            . "ORDER BY msg.timestamp DESC; ";
        $result = $relation->queryAsControlUser($query);

        // Extract messages from query result, setting message as false if none are present
        $foundEnd = false;
        if ($result === false) {
            $messages = false;
        } else if ($result->num_rows > 0) {
            $i = 0;
            $messages = [];
            while(true) {
                $row = $result->fetch_assoc();

                // check if found end and send complete message if true
                if ($row === null) {
                    $foundEnd = true;
                    break;
                }

                // skip if the message was the last one recorded
                $rowDate = \DateTime::createFromFormat('Y-m-d H:i:s', $row['timestamp']);
                if ($rowDate >= $lastDate) {
                    continue;
                }
                $messages[$i]['id'] = $row['id'];
                $messages[$i]['message'] = $row['message'];
                $messages[$i]['sender'] = $row['sender'];
                $messages[$i]['timestamp'] = $row['timestamp'];
                $messages[$i]['seen'] = $row['seen'];
                $i++;

                if ($limit > 0 && $i >= $limit && $rowDate != $lastDate) {
                    break;
                }
            }

        } else {
            $messages = false;
            $foundEnd = true;
        }

        // Sends message to client
        if ($messages !== false) {
            $successMessage = Message::success(__('Results found: ' . count($messages)));
            $this->response->setRequestStatus(true);
            $this->response->addJSON('message', $successMessage);
            $this->response->addJSON('data', $messages);
        } else if($foundEnd === false) {
            $failMessage = Message::error(__('Error getting messages from server.'));
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $failMessage);
        } else {
            $this->response->setRequestStatus(true);
        }
    }

    /**
     * Sends message to specified receiver with the specified message
     * Id parameter is optional and not recommended for use in anything other than testing
     *
     * @param string $sender   sender of message
     * @param string $receiver receiver of message
     * @param string $message  message to send/store
     * @param string $id       id of user (optional)
     *
     * @return string
     */
    public function sendMessage($sender, $receiver, $message, $id = null)
    {
        $relation = new Relation();
        if (!is_null($id)) {
            $query = "INSERT INTO phpmyadmin.pma__messages "
                . "(`id`, `sender`, `receiver`, `timestamp`, `message`, `seen`) "
                . "VALUES ($id,'$sender', '$receiver', utc_timestamp(), '$message', 0);";
        } else {
            $query = "INSERT INTO phpmyadmin.pma__messages "
                . "(`sender`, `receiver`, `timestamp`, `message`, `seen`) "
                . "VALUES ('$sender', '$receiver', utc_timestamp(), '$message', 0);";
        }

        return $relation->queryAsControlUser($query, true);
    }

    /**
     *  Deletes message specified by message id
     *
     * @param string $id id number of message to delete
     *
     * @return string query delete result
     */
    public function deleteMessage($id)
    {
        $relation = new Relation();
        $query = "DELETE FROM phpmyadmin.pma__messages WHERE id = $id;";

        return $relation->queryAsControlUser($query, true);
    }

    /*No idea why this function is here, delete if not needed.

    private function getReadOrStatement(Array $idList)
    {
        if (!isset($idList) || count($idList) == 0) {
            return null;
        }
        $query = "";
        $first = true;
        foreach ($idList as $i) {
            if (! $first) {
                $query .= " OR ";
            }
            $query .= "msg.id = $i";
            $first = false;
        }
        return $query;
    }
    */
}