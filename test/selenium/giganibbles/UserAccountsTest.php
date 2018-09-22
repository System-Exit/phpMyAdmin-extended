<?php
/**
 * Selenium tests for user accounts testing
 *
 * @package PhpMyAdmin/Tests/Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * Class UserAccountsTest
 *
 * @package PhpMyAdmin\Tests\Selenium
 */
class UserAccountsTest extends TestBase
{

    /**
     * Variable for name of testing user
     * Used with user export test
     *
     * @var string
     */
    protected $testUser;

    /**
     * Variable for test user's host
     * Used with user export test
     *
     * @var string
     */
    protected $testHost;

    /**
     * Set up for testing
     *
     * @throws \Exception
     */
    protected function setup()
    {
        parent::setUp();
        $this->testUser = $GLOBALS['TESTSUITE_USER'];
        $this->testHost = $GLOBALS['TESTSUITE_SERVER'];
    }

    /**
     * Set up page for each test
     */
    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
    }

    /**
     * Tests merging of user pages
     *
     * Tests:
     * - User management element is present on page
     * - User group management element is present on page
     *
     * @return void
     */
    public function testMerge()
    {
        // Navigate to user accounts page
        $link = $this->waitForElement('byXPath', "//a[contains(@href, 'server_privileges.php')]");
        $link->click();
        $this->waitAjax();

        // Check if both users and user groups lists are present
        $this->assertTrue($this->isElementPresent('byId', "usersForm"));
        $this->assertTrue($this->isElementPresent('byId', "userGroupsForm"));
    }

    /**
     * Tests for exporting of users and user groups
     *
     * Tests:
     * - Check if single user export code window appears
     * - Check if multiple user export code window appears
     * - Check if link for user group exporting is present
     * - Check if user group export code window appears
     *
     * @return void
     */
    public function testExport()
    {
        $this->navigateToUserAccounts();

        // Test if user export text appears
        $this->waitForElement('byXPath', "//a[contains(@href, '$this->testUser')"
            . " and contains(@href, '$this->testHost')]")->click();
        $this->waitAjax();
        $this->assertTrue($this->isElementPresent('byXPath', "//div[@class='CodeMirror-lines']"));

        $this->navigateToUserAccounts();

        // Test if multiple user export text appears
        $this->waitForElement('byXPath', "//input[@id='usersForm_checkall']")->click();
        $this->waitForElement('byXPath', "//button[@name='submit_mult']")->click();
        $this->waitAjax();
        $this->assertTrue($this->isElementPresent('byXPath', "//div[@class='CodeMirror-lines']"));

        $this->navigateToUserAccounts();

        // Test if export user element is present
        $this->assertTrue($this->isElementPresent('byXPath', "//a[contains(@href, 'exportUserGroup')]"));

        // Test if group export text appear
        $ele = $this->waitForElement('byId','fieldset_export_user_group');
        $this->moveto($ele);
        $this->waitForElement('byXPath', "//a[contains(@href, 'exportUserGroup')]")->click();
        $this->waitAjax();
        $this->assertTrue($this->isElementPresent('byXPath', "//div[@class='CodeMirror-lines']"));

    }

    /**
     * Helper function to go directly to user accounts page
     *
     * @return void
     */
    private function navigateToUserAccounts()
    {
        $link = $this->waitForElement('byXPath', "//a[contains(@href, 'server_privileges.php')]");
        $link->click();
        $this->waitAjax();
    }
}
