<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 8/17/2018
 * Time: 1:11 PM
 */

namespace PhpMyAdmin\Tests\Selenium;


use PhpMyAdmin\Relation;

class MessagesTest extends TestBase
{
    /**
     * Username for sender of message
     *
     * @access private
     * @var string
     */
    private $sender;

    /**
     * Username for receiver of message
     *
     * @access private
     * @var string
     */
    private $receiver;

    /**
     * Test message to be sent
     *
     * @access private
     * @var string
     */
    private $message;

    protected function setup()
    {
        parent::setUp();

        $this->sender = $GLOBALS['TESTSUITE_USER'];
        $this->receiver = "pma_user";
        $this->message = "Test message";
    }

    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
    }

    public function testSendMessage()
    {
        $link = $this->waitForElement('byXPath', "//a[contains(@href, 'server_messages.php')]");
        $link->click();
        $link->click();
        $this->waitAjax();

        //Let page load
        $this->waitAjax();

        // Add message to send
        $this->scrollIntoView('form_message');
        $ele = $this->waitForElement("byId", "form_message");
        $this->moveto($ele);
        $ele->click();

        $this->waitAjax();
        $messageField = $this->waitForElement("byName", "form_message");
        $messageField->value($this->message);

        // Add receiver to receive message
        $this->scrollIntoView('form_receiver');
        $ele = $this->waitForElement("byId", "form_receiver");
        $this->moveto($ele);
        $ele->click();

        $this->waitAjax();
        $receiverField = $this->waitForElement("byName", "form_receiver");
        $receiverField->value($this->receiver);

        // Attempt to send message
        $this->scrollIntoView('');
        $sendButton = $this->waitForElement("byValue", "Send");
        $this->moveto($sendButton);
        $sendButton->click();

        // Check if the sending was successful
        $relation = new Relation();
        $query = "SELECT * FROM `phpmyadmin`.`pma__messages` WHERE receiver = '$this->receiver';";
        $result = $relation->queryAsControlUser($query);
        $this->assertTrue($result != false && $result->num_rows > 0);

        // Delete test message from database
        $query = "DELETE FROM `phpmyadmin`.`pma__messages` WHERE receiver = '$this->receiver';";
        $relation->queryAsControlUser($query);
    }
}
