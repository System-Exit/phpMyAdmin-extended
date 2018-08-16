<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 8/15/2018
 * Time: 11:48 PM
 */

namespace PhpMyAdmin\Tests\Controllers\Giganibbles;

use PhpMyAdmin\Controllers\Giganibbles\ServerMessagesController;
use PhpMyAdmin\libraries\classes\Relation;
use PhpMyAdmin\Tests\PmaTestCase;
use PHPUnit\Framework\TestCase;

class ServerMessagesControllerTest extends PmaTestCase
{

    protected $relation;
    protected $receiver;
    protected $message;
    protected $sender;

    protected function setup()
    {
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
        $send_result = ServerMessagesController::sendMessage($this->sender, $this->receiver, $this->message, -1);
        $get_result = ServerMessagesController::getMessages($this->receiver);
        $delete_result = ServerMessagesController::deleteMessage(-1);

        assertTrue($send_result);
        assertTrue($get_result->num_rows >= 1);
        assertTrue($delete_result);
    }
}
