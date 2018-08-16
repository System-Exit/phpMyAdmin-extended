<?php
/**
 * Created by PhpStorm.
 * User: lukel
 * Date: 18/08/08
 * Time: 4:58 PM
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

    public function indexAction()
    {
        global $cfg;

        // pass to ajax function if page call is an ajax request
        // Note: error handling and validation is done in sendAction().
        if ($this->response->isAjax()
            && isset($_POST['form_send_check'])
        ) {
            $this->sendAction();
            return;
        }

        // get new response
        $response = $this->response;

        // upload script
        $header = $response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('Giganibbles/server_messages.js');

        // Get current user
        $user = $cfg['Server']['user'];

        // Get messages for current user
        $messages = $this->getMessages($user);

        $response->addHTML($this->template->render(
            'Giganibbles/server_messages',
            [
                // Passes messages array
                'messages' => $messages
            ]
        ));
    }

    /**
     * Stores a message in the database. This message has three params:
     *
     *  - 'sender'      : string (must match current user)
     *  - 'receiver'    : string (must be in mysql.user)
     *  - 'message'     : string (not empty)
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
            //$message_out = Message::error(__('Error: Message and receiver need to be filled out.'));
            //$response->addJSON('error', $message_out);
            return;
        }

        $sender = $cfg['Server']['user'];
        $receiver = $this->dbi->escapeString($_POST['form_receiver']);
        $message = $this->dbi->escapeString($_POST['form_message']);

        // checks if user is current
        if (empty($sender)) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Error: there was an issue when checking the current user.'));
            $response->addJSON('error', $message_out);
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
            $response->addJSON('error', $message_out);
            return;
        }

        // check if message is empty
        if (empty($message)) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Error: the message has not been set.'));
            $response->addJSON('error', $message_out);
            return;
        }

        // create new message
        $success = $this->sendMessage($sender, $receiver, $message);

        if ($success === false) {
            $response->setRequestStatus(false);
            $message_out = Message::error(__('Could not send the message.'));
            $response->addJSON('error', $message_out);
        } else {
            $response->setRequestStatus(true);
            // TODO when messages are sent they stop all other content from displaying.
            // $message_out = Message::success(__('The message has been sent.'));
            // $response->addJSON('message', $message_out);
        }

    }

    /**
     *  Queries and returns the messages that the specified user has received
     *
     * @param $user
     *
     * @return string array of strings of data found from query
     * @return boolean false on failure to retrieve any messages
     */
    public function getMessages($user)
    {
        $relation = new Relation();

        // Query for all messages sent to current user
        $query = "SELECT msg.message, msg.sender, msg.timestamp "
            . "FROM phpmyadmin.pma__messages msg "
            . "WHERE msg.receiver LIKE '$user' "
            . "ORDER BY timestamp DESC;";
        $result = $relation->queryAsControlUser($query);

        // Extract messages from query result, setting message as false if none are present
        if($result !== false && $result->num_rows > 0) {
            $i = 0;
            $messages = [];
            while($row = $result->fetch_assoc()) {
                $messages[$i]['message'] = $row['message'];
                $messages[$i]['sender'] = $row['sender'];
                $messages[$i]['timestamp'] = $row['timestamp'];
                $i++;
            }
            return $messages;
        } else {
            $messages = false;
        }
    }

    /**
     * Sends message to specified receiver with the specified message
     * Id parameter is optional and not recommended for use in anything other than testing
     *
     * @param $sender
     * @param $receiver
     * @param $message
     * @param $id optional
     *
     * @return boolean
     */
    public function sendMessage($sender, $receiver, $message, $id = null)
    {
        $relation = new Relation();
        if (!is_null($id)) {
            $query = "INSERT INTO phpmyadmin.pma__messages "
                . "(`id`, `sender`, `receiver`, `timestamp`, `message`, `seen`) "
                . "VALUES ($id,'$sender', '$receiver', now(), '$message', 0);";
        } else {
            $query = "INSERT INTO phpmyadmin.pma__messages "
                . "(`sender`, `receiver`, `timestamp`, `message`, `seen`) "
                . "VALUES ('$sender', '$receiver', now(), '$message', 0);";
        }

        return $relation->queryAsControlUser($query, true);
    }

    /**
     *  Deletes message specified by message id
     *
     * @param $id
     *
     * @return boolean
     */
    public function deleteMessage($id)
    {
        $relation = new Relation();
        $query = "DELETE FROM phpmyadmin.pma__messages WHERE id = $id;";

        return $relation->queryAsControlUser($query, true);
    }
}