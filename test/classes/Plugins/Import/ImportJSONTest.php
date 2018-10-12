<?php
/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportJSON class
 *
 * @package PhpMyAdmin-test
 */

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Plugins\Import\ImportJSON;

/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportJSON class
 *
 * @package PhpMyAdmin-test
 */
class ImportJSONTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new ImportJSON();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test.json';
        $GLOBALS['import_text'] = 'ImportJSON_Test';
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Xml';
        $GLOBALS['import_handle'] = new File($GLOBALS['import_file']);
        $GLOBALS['import_handle']->open();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for doImport
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImport()
    {
        // $sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        // Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        // Test function called
        $this->object->doImport();

        // Check that sql was executed
        $this->assertContains(
            'INSERT INTO `phpmyadmin`.`pma_bookmark` (`id`, `dbase`, `user`, `label`, `query`) '
            . 'VALUES',
            $sql_query
        );
    }
}