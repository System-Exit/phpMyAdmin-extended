<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 9/3/2018
 * Time: 4:45 PM
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;


use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;
use PhpMyAdmin\Relation;


/**
 * Handles the import for the JSON format
 * Only data is imported, as exported tables lack a structure specification
 * This can be updated alongside the JSON export if we want to include structure as well
 *
 * @package    PhpMyAdmin-Import
 * @subpackage XML
 */
class ImportJSON extends ImportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setProperties();
    }

    /**
     * Sets the import plugin properties.
     * Called in the constructor.
     *
     * @return void
     */
    protected function setProperties()
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('JSON'));
        $importPluginProperties->setExtension('json');
        $importPluginProperties->setMimeType('text/json');
        $importPluginProperties->setOptions([]);
        $importPluginProperties->setOptionsText(__('Options'));

        $this->properties = $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array &$sql_data 2-element array with sql data
     *
     * @return void
     */
    public function doImport(array &$sql_data = [])
    {
        global $error, $timeout_passed, $finished, $db;

        $i = 0;
        $len = 0;
        $buffer = "";

        /**
         * Read in the file via Import::getNextChunk so that
         * it can process compressed files
         */
        while (!($finished && $i >= $len) && !$error && !$timeout_passed) {
            $data = $this->import->getNextChunk();
            if ($data === false) {
                /* subtract data we didn't handle yet and stop processing */
                $GLOBALS['offset'] -= strlen($buffer);
                break;
            } elseif ($data === true) {
                /* Handle rest of buffer */
            } else {
                /* Append new data to buffer */
                $buffer .= $data;
                unset($data);
            }
        }

        unset($data);

        /**
         * Load the JSON string
         */
        $data = json_decode($buffer,true);

        /**
         * Check if decoding was successful
         */
        if(!isset($data) || is_null($data))
        {
            // TODO: error message
        }

        /**
         * Parse the data and insert it into the specified table
         *
         * Note that each row of data has its own insert statement.
         * While less efficient than combined value insert, order may be manually
         * mixed, so separate statements are ideal to avoid issues with this.
         *
         * Also uses r
         */
        $relation = new Relation();
        foreach($data as $sect)
        {
            if($sect["type"] == "table")
            {
                $tableName = $sect["name"];
                $tableDatabase = $sect["database"];
                $tableData = $sect["data"];
                foreach($tableData as $row)
                {
                    // Start of insert, specifies table
                    $query = "INSERT INTO `$tableDatabase`.`$tableName` (";
                    // Specifies the columns to insert data for
                    foreach ($row as $key => $value)
                    {
                        $query .= "$key,";
                    }
                    $query = substr_replace($query, ")", strrpos($query, ","), 1);
                    $query .= " VALUES(";
                    // Specifies data values to insert
                    foreach ($row as $value)
                    {
                        $query .= "'$value',";
                    }
                    $query = substr_replace($query, ")", strrpos($query, ","), 1);
                    $query .= ";";
                    $relation->queryAsControlUser($query, true);
                }
            }
        }

    }

}