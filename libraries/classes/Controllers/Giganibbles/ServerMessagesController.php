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

    protected $relation;

    protected $user;

    protected $result;

    public function indexAction()
    {
        global $cfg;
        // Connect to sql
        $relation = new Relation();
        // Get current user
        $user = $cfg['Server']['user'];
        // Get messages from database
        $result = $relation->queryAsControlUser("SELECT `message` FROM `pma__messages` WHERE `receiver` LIKE `$user`");

        $response = Response::getInstance();

        $response->addHTML($this->template->render(
            'Giganibbles/server_messages',
            [
                // vars go here!
                'result' => $this->result
            ]
        ));

         return $result->num_rows;
    }

}

$c = new ServerMessagesController();
$r = $c->indexAction();
echo $r;