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
        // Get access to database
        /*DatabaseInterface::load(); (This line might be needed?)*/
        $this->dbi->connect(DatabaseInterface::CONNECT_CONTROL);
        $this->dbi->selectDb('phpmyadmin');
        // Get current user
        $user = $cfg['Server']['user'];
        // Get messages from database
        $query = "SELECT `msg`.`receiver` FROM `pma__messages` `msg` WHERE `msg`.`receiver` LIKE '$user'";
        $result = $this->dbi->query($query);
        
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