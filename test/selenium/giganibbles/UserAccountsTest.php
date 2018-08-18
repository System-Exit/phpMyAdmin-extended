<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 8/18/2018
 * Time: 11:33 AM
 */

namespace PhpMyAdmin\Tests\Selenium;


class UserAccountsPageTest extends TestBase
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

    protected function setup()
    {
        parent::setUp();
        $this->testUser = $GLOBALS['TESTSUITE_USER'];
        $this->testHost = $GLOBALS['TESTSUITE_SERVER'];
    }

    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
    }

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

    public function testExport()
    {
        $this->navigateToUserAccounts();

        // Test if user export text appears
        $this->waitForElement('byXPath', "//a[contains(@href, '$this->testUser')"
            ." and contains(@href, '$this->testHost')]")->click();
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

        // Test if group export text appears
        /* NOT WORKING, SCROLL FAILS TO FUNCTION
        $this->scrollIntoView('fieldset_export_user_group');
        $this->waitForElement('byXPath', "//a[contains(@href, 'exportUserGroup')]")->click();
        $this->waitAjax();
        $this->assertTrue($this->isElementPresent('byXPath', "//div[@class='CodeMirror-lines']"));
        */

    }

    /**
     * Navigates to user accounts page
     */
    private function navigateToUserAccounts()
    {
        $link = $this->waitForElement('byXPath', "//a[contains(@href, 'server_privileges.php')]");
        $link->click();
        $this->waitAjax();
    }
}
