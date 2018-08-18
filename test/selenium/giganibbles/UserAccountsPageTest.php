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
    protected function setup()
    {
        parent::setUp();
    }

    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
    }

    public function testMerge()
    {
        $link = $this->waitForElement('byXPath', "//a[contains(@href, 'server_privileges.php')]");
        $link->click();
        $this->waitAjax();

        $this->assertTrue($this->isElementPresent('byId', "usersForm"));
        $this->assertTrue($this->isElementPresent('byId', "userGroupsForm"));
    }
}
