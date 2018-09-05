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

    /**
     * Tests message sending functionality for sending a message to oneself
     *
     * Tests:
     * - if sent message is present in message database
     * - if new message is visible on loaded page
     */
    public function testSendMessage()
    {
        // Goto messages page
        $this->goToMessagesPage();

        // Add message to send
        //$this->scrollIntoView('form_message');
        $ele = $this->waitForElement("byId", "form_message");
        $this->moveto($ele);
        $ele->click();

        $this->waitAjax();
        $messageField = $this->waitForElement("byName", "form_message");
        $messageField->value($this->message);

        // Add receiver to receive message
        //$this->scrollIntoView('form_receiver');
        $ele = $this->waitForElement("byId", "form_receiver");
        $this->moveto($ele);
        $ele->click();

        $this->waitAjax();
        $receiverField = $this->waitForElement("byName", "form_receiver");
        $receiverField->value($this->receiver);

        // Attempt to send message
        $sendButton = $this->waitForElement("byId", "message_form_send");
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
        $this->waitForElement('byXPath', "//li[@class='message_body']");
        $this->assertTrue($this->isElementPresent('byXPath', "//p[text()='$this->message']"));

        // Delete test message
        $this->deleteTestMessages();
    }

    /**
     * Tests message receiving
     *
     * Tests:
     * - Created message is visible on messages page
     * - Message is marked as new when first loaded
     * - Message is not marked when loaded again
     */
    public function testMessageReceive()
    {
        // Load messages page to clear any new messages
        $this->goToMessagesPage();

        // Creates a new message directly
        $query = "INSERT INTO phpmyadmin.pma__messages "
            . "(`sender`, `receiver`, `timestamp`, `message`, `seen`) "
            . "VALUES ('$this->sender', '$this->receiver', utc_timestamp(), '$this->message', 0);";
        $this->dbQuery($query);

        // Reload messages page
        $this->refresh();

        // Check if new message is present
        $this->waitForElement('byXPath', "//li[@class='message_body']");
        $this->assertTrue($this->isElementPresent('byXPath',  "//p[text()='$this->message']"));

        // Check if message is marked as new
        $this->assertTrue($this->isElementPresent('byXPath',
            "//li[p[text()='$this->message'] and p[@class='message_new']]"));

        // Reload messages page
        $this->refresh();

        // Check that the message is no longer marked as new
        $this->waitForElement('byXPath', "//li[@class='message_body']");
        $this->assertFalse($this->isElementPresent('byXPath',
            "//li[p[text()='$this->message'] and p[@class='message_new']]"));

        // Delete test message
        $this->deleteTestMessages();
    }

    /**
     * Helper function to go directly to messages page
     */
    private function goToMessagesPage()
    {
        // Go to messages page
        $this->expandMore();
        $this->waitForElement('byXPath', "//a[contains(@href, 'server_messages.php')]")->click();

        //Let page load
        $this->waitAjax();
    }

    /**
     * Helper function to remove all created test messages
     */
    private function deleteTestMessages()
    {
        // Delete test message from database
        $query = "DELETE FROM `phpmyadmin`.`pma__messages` WHERE message = '$this->message';";
        $this->dbQuery($query);
    }
}
