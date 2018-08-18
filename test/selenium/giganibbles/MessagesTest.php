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
        $this->receiver = $GLOBALS['TESTSUITE_USER'];
        $this->message = "Selenium test message";
    }

    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
    }

    public function testSendMessage()
    {
        // Go to messages page
        $this->expandMore();
        $this->waitForElement('byXPath', "//a[contains(@href, 'server_messages.php')]")->click();
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
        $sendButton = $this->waitForElement("byXPath", "//input[@value='Send']");
        $this->moveto($sendButton);
        $sendButton->click();

        // Wait for message to send
        $this->waitAjax();

        // Check if the sending was successful
        $query = "SELECT * FROM `phpmyadmin`.`pma__messages` WHERE message = '$this->message';";
        $result = $this->dbQuery($query);
        $this->assertTrue($result != false && $result->num_rows > 0);

        // Reload messages page
        $this->refresh();

        //Check if new message is visible
        $this->assertTrue($this->isElementPresent('byXPath', "//p[text()='$this->message']"));

        // Delete test message from database
        $query = "DELETE FROM `phpmyadmin`.`pma__messages` WHERE message = '$this->message';";
        $this->dbQuery($query);
    }
}
