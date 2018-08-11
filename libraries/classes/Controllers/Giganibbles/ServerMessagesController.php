<?php
/**
 * Created by PhpStorm.
 * User: lukel
 * Date: 18/08/08
 * Time: 4:58 PM
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Giganibbles;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Message;
use PhpMyAdmin\Util;

/**
 * Class ServerMessagesController
 *
 * A controller which calls the twig for our messages page.
 *
 * @package PhpMyAdmin\Controllers\Giganibbles
 */
class ServerMessagesController extends Controller
{
    protected $user;
    protected $query;
    protected $result;

    protected $sender;
    protected $timestamp;
    protected $message;

    public function indexAction()
    {
        global $cfg;

        // Get current user
        $user = $cfg['Server']['user'];
        // Query for all messages sent to current user
        $query = "SELECT msg.receiver FROM phpmyadmin.pma__messages msg WHERE msg.receiver LIKE '$user'";
        $relation = new Relation();
        $result = $relation->queryAsControlUser($query);

        $response = Response::getInstance();

        $response->addHTML($this->template->render(
            'Giganibbles/server_messages',
            [
                // vars go here!
                'result' => $result->num_rows
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
        $response = Response::getInstance();

        // checks if all data is set. If not, throws an error.
        if (empty($_POST['sender'])
            || empty($_POST['receiver'])
            || empty($_POST['message'])
        ) {
            $response->setRequestStatus(false);
            $message = Message::error(__('An unexpected error occurred.'));
            $response->addJSON('message', $message);
            return;
        }

        $sender = $_POST['sender'];
        $receiver = $_POST['receiver'];
        $message = $_POST['message'];

        // checks if user is current
        if ($cfg['Server']['user'] != $sender) {
            $response->setRequestStatus(false);
            $message = Message::error(__('Error: there was an issue when checking the current user.'));
            $response->addJSON('message', $message);
            return;
        }

        // check if receiver exists
        $relation = new Relation();
        $receiver_result = $relation->queryAsControlUser(
            "SELECT `User` FROM `mysql.user` WHERE `User` LIKE '$receiver';",
            false);
        if (empty($receiver_result) || $receiver_result->num_rows == 0)
        {
            $response->setRequestStatus(false);
            $message = Message::error(__('Error: the receiver is not a valid user.'));
            $response->addJSON('message', $message);
            return;
        }

        // check if message is empty
        if (empty($message))
        {
            $response->setRequestStatus(false);
            $message = Message::error(__('Error: the message has not been set.'));
            $response->addJSON('message', $message);
            return;
        }

        // create new message
        $success = $relation->queryAsControlUser(
            "INSERT INTO `phpmyadmin.pma__messages` VALUES " .
                "($sender, $receiver, $message, now());"
        );

        if ($success === false)
        {
            $response->setRequestStatus(false);
            $message = Message::error(__('Could not send the message.'));
            $response->addJSON('message', $message);
        } else {
            $response->setRequestStatus(true);
            $message = Message::success(__('The message has been sent.'));
            $response->addJSON('message', $message);
        }

    }

}