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

    public function indexAction()
    {
        // function processing goes here.

        $response = Response::getInstance();

        $response->addHTML($this->template->render(
            'Giganibbles/server_messages',
            [
                // vars go here!
            ]
        ));
    }

}