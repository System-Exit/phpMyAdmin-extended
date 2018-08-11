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

}