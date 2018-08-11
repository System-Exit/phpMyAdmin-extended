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
    protected $messages;

    public function indexAction()
    {
        global $cfg;

        // Get current user
        $user = $cfg['Server']['user'];
        // Query for all messages sent to current user
        $query = "SELECT msg.message, msg.sender, msg.timestamp  FROM phpmyadmin.pma__messages msg WHERE msg.receiver LIKE '$user'";
        $relation = new Relation();
        $result = $relation->queryAsControlUser($query);
        // Extract messages from query result, setting message as false if none are present
        if($result->num_rows > 0)
        {
            $i = 0;
            while($row = $result->fetch_assoc())
            {
                $messages[$i]['message'] = $row['message'];
                $messages[$i]['sender'] = $row['sender'];
                $messages[$i]['timestamp'] = $row['timestamp'];
                $i++;
            }
        }
        else
        {
            $messages = false;
        }

        $response = Response::getInstance();

        $response->addHTML($this->template->render(
            'Giganibbles/server_messages',
            [
                // Passes messages array
                'messages' => $messages
            ]
        ));
    }

}