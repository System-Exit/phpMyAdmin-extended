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
     * Set up for testing
     *
     * @throws \Exception
     * @return void
     */
    protected function setup()
    {
        parent::setUp();
    }

    /**
     * Set up page for each test
     *
     * @return void
     */
    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
    }

    /**
     * Tests that all elements are present.
     *
     * @return void
     */
    public function testElementsPresent()
    {
        // Navigate to user statistics page
        $this->navigateToUserStatistics();

        // Check if both users and user groups lists are present
        $this->assertTrue($this->isElementPresent('byId', "user_stats_info"));
        $this->assertTrue($this->isElementPresent('byId', "user_stats_permissions"));
        $this->assertTrue($this->isElementPresent('byId', "user_stats_usage"));
        $this->assertTrue($this->isElementPresent('byId', "user_stats_export"));
    }

    /**
     * Helper function to go directly to user stats page.
     *
     * @return void
     */
    private function navigateToUserStatistics()
    {
        $link = $this->waitForElement('byXPath', "//a[contains(@href, 'server_user_stats.php')]");
        $link->click();
        $this->waitAjax();
    }
}
