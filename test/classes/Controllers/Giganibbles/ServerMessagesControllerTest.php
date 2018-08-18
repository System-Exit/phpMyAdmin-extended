<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 8/15/2018
 * Time: 11:48 PM
 */

namespace PhpMyAdmin\Tests\Controllers\Giganibbles;

use PhpMyAdmin\Controllers\Giganibbles\ServerMessagesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\libraries\classes\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PHPUnit\Framework\TestCase;

/**
 * Class ServerMessagesControllerTest
 * @package PhpMyAdmin\Tests\Controllers\Giganibbles
 *
 * NOTE: NOT WORKING
 */

class ServerMessagesControllerTest extends PmaTestCase
{

    protected $SMC;
    protected $receiver;
    protected $message;
    protected $sender;

    protected function setup()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['table'] = "table";
        $GLOBALS['db'] = 'db';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        //$_SESSION

        if (!defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'Other');
        }

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        $container = Container::getDefaultContainer();
        $container->set('db', 'db');
        $container->set('table', 'table');
        $container->set('dbi', $GLOBALS['dbi']);
        $this->_response = new ResponseStub();
        $container->set('PhpMyAdmin\Response', $this->_response);
        $container->alias('response', 'PhpMyAdmin\Response');

        $this->SMC = new ServerMessagesController($this->_response, $dbi);
        $this->receiver = 'test_receiver';
        $this->message = 'test_message';
        $this->sender = 'test_sender';
    }

    protected function tearDown()
    {

    }

    /**
     * Test for messages
     *
     * @return void
     */
    public function testMessages()
    {
        $send_result = $this->SMC->sendMessage($this->sender, $this->receiver, $this->message, -1);
        $get_result = $this->SMC->getMessages($this->receiver);
        $delete_result = $this->SMC->deleteMessage(-1);

        $this->assertTrue($send_result);
        $this->assertTrue($get_result->num_rows >= 1);
        $this->assertTrue($delete_result);
    }
}
