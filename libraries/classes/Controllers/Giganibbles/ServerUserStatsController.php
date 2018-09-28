<?php
/**
 * Message controller for messages page
 *
 * @package PhpMyAdmin\Controllers\Giganibbles
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Giganibbles;

use phpDocumentor\Reflection\Types\Boolean;
use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Message;

/**
 * Class ServerMessagesController
 *
 * A controller which calls the twig for our messages page.
 *
 * @package PhpMyAdmin\Controllers\Giganibbles
 */
class ServerUserStatsController extends Controller
{

    /**
     * Handles actions to perform on loading the page
     *
     * @return void
     */
    public function indexAction()
    {
        global $cfg;

        $user = $cfg['Server']['user'];
        $host = $cfg['Server']['host'];

        // Get user information
        $info_list = [
            'name'      => $user,
            'date'      => 'None', // TODO: Get date of user creation
            'server'    => $host
        ];
        // Get user permissions
        $permissions = [
            'pma'       => $this->getUserPmaPrivs($user),
            'mysql'     => $this->getUserMySqlPrivs($user, $host)
        ];
        // Get user usage statistics
        $usage_list = [
            ['type' => 'None', 'value' => 'None']
        ];

        $this->response->addHTML($this->template->render(
            'Giganibbles/server_user_stats',
            [
                'info_list'     => $info_list,
                'permissions'   => $permissions,
                'usage_list'    => $usage_list
            ]
        ));
    }

    /**
     * Accesses user permissions from the mysql database. This will build
     * @param string $user Username of user to get privileges for
     * @param string $host Host of user to get privileges for
     * @return array An array of all values to be added. All values are returned
     *               as strings to be listed onto the page.
     */
    private function getUserMySqlPrivs($user, $host) : array {
        $relation = new Relation();

        // Get the permissions for the user
        $query = "SELECT * "
            . "FROM mysql.user "
            . "WHERE user = '$user' "
            . "AND host = '$host'; ";
        $result = $relation->queryAsControlUser($query);

        // Parse through all permissions and add them to categories
        $privilegeCategories = $this->extractSqlPermissions($result);
        // Build strings for each non-empty category and adds it to privileges strings
        $privilegesStrings = [];
        foreach($privilegeCategories as $category => $privileges)
        {
            // Check if category is empty, skipping category if so
            if(sizeof($privileges) == 0) continue;

            // Create and add string to privileges strings
            $catToAdd = "$category: ";
            for ($i = 0; $i < count($privileges); $i++) {
                $catToAdd .= str_replace("_", " ", $privileges[$i]) . ", ";
            }
            $catToAdd = rtrim($catToAdd, ", ");
            $privilegesStrings[] = $catToAdd;
        }

        return $privilegesStrings;
    }

    /**
     * Accesses user permissions from the PMA usergroups list. This will build a string of
     * permissions for each category (Server, Database, Table) of tabs and return them
     * in an array.
     * @param string $user Username of user to get user group permissions from
     * @return array An array of all values to be added. All values are returned
     *               as strings to be listed onto the page.
     */
    private function getUserPmaPrivs($user) : array {
        $relation = new Relation();

        // Get user group of user
        $usergroup = $this->getUserGroupOfUser($user);
        // If the user doesn't have a user group, indicate this in a single value in array
        if(empty($usergroup))
        {
            return ["User has no user group and is not restricted by tab view."];
        }

        // Start string array to return with name of user group
        $privilegesStrings = ["User group: $usergroup"];
        // Get the permissions for the user group
        $query = "SELECT * "
            . "FROM phpmyadmin.pma__usergroups "
            . "WHERE usergroup = '$usergroup'; ";
        $result = $relation->queryAsControlUser($query);

        // Parse through server, database and table level tab permissions
        $privilegeCategories['Server'] = [];
        $privilegeCategories['Database'] = [];
        $privilegeCategories['Table'] = [];
        while($row = $result->fetch_assoc())
        {
            // Skip row if privilege is not allowed
            if($row['allowed'] == 'N') continue;

            // Determine privilege category and store it in privileges array
            if(strpos($row['tab'], "server_") !== false)
                $privilegeCategories['Server'][] = explode('server_', $row['tab'])[1];
            if(strpos($row['tab'], "db_") !== false)
                $privilegeCategories['Database'][] = explode('db_', $row['tab'])[1];
            if(strpos($row['tab'], "table_") !== false)
                $privilegeCategories['Table'][] = explode('table_', $row['tab'])[1];
        }

        // Build strings for each non-empty category and adds it to privileges strings
        foreach($privilegeCategories as $category => $privileges)
        {
            // Check if category is empty, skipping category if so
            if(sizeof($privileges) == 0) continue;

            // Create and add string to privileges strings
            $catToAdd = "$category: ";
            for ($i = 0; $i < count($privileges); $i++) {
                $catToAdd .= str_replace("_", " ", $privileges[$i]) . ", ";
            }
            $catToAdd = rtrim($catToAdd, ", ");
            $privilegesStrings[] = $catToAdd;
        }

        // Return all valid built strings for privileges
        return $privilegesStrings;
    }

    /**
     * Convenience method for getting the usergroup of the current user.
     * @param string $user User name of user to get user group of
     * @return mixed A usergroup for the current user, or null if the user has
     *               not been assigned a usergroup.
     */
    private function getUserGroupOfUser($user){
        $relation = new Relation();

        // Query for user group of user
        $query = "SELECT username, usergroup "
            . "FROM phpmyadmin.pma__users "
            . "WHERE username = '$user'; ";
        $result = $relation->queryAsControlUser($query);
        $row = $result->fetch_assoc();

        // Return user group
        return $row['usergroup'];
    }

    /**
     * Method for categorizing and prettifying privileges from actual privilege names
     * @param array $result Result of user privileges query
     * @return array Array of categorized privileges
     */
    private function extractSqlPermissions($result)
    {
        // Parse through all permissions and add them to categories
        $privilegeCategories['Data'] = [];
        $privilegeCategories['Structure'] = [];
        $privilegeCategories['Administration'] = [];
        $row = $result->fetch_assoc();
        // Data privileges
        if($row['Select_priv'] == 'Y')              $privilegeCategories['Data'][] = "select";
        if($row['Insert_priv'] == 'Y')              $privilegeCategories['Data'][] = "insert";
        if($row['Update_priv'] == 'Y')              $privilegeCategories['Data'][] = "update";
        if($row['Delete_priv'] == 'Y')              $privilegeCategories['Data'][] = "delete";
        if($row['File_priv'] == 'Y')                $privilegeCategories['Data'][] = "file";
        // Structure privileges
        if($row['Create_priv'] == 'Y')              $privilegeCategories['Structure'][] = "create";
        if($row['Alter_priv'] == 'Y')               $privilegeCategories['Structure'][] = "alter";
        if($row['Index_priv'] == 'Y')               $privilegeCategories['Structure'][] = "index";
        if($row['Drop_priv'] == 'Y')                $privilegeCategories['Structure'][] = "drop";
        if($row['Create_tmp_table_priv'] == 'Y')    $privilegeCategories['Structure'][] = "create temporary tables";
        if($row['Show_view_priv'] == 'Y')           $privilegeCategories['Structure'][] = "show view";
        if($row['Create_routine_priv'] == 'Y')      $privilegeCategories['Structure'][] = "create routine";
        if($row['Alter_routine_priv'] == 'Y')       $privilegeCategories['Structure'][] = "alter routine";
        if($row['Execute_priv'] == 'Y')             $privilegeCategories['Structure'][] = "execute";
        if($row['Create_view_priv'] == 'Y')         $privilegeCategories['Structure'][] = "create view";
        if($row['Event_priv'] == 'Y')               $privilegeCategories['Structure'][] = "event";
        if($row['Trigger_priv'] == 'Y')             $privilegeCategories['Structure'][] = "trigger";
        // Administration privileges
        if($row['Grant_priv'] == 'Y')               $privilegeCategories['Administration'][] = "grant";
        if($row['Super_priv'] == 'Y')               $privilegeCategories['Administration'][] = "super";
        if($row['Process_priv'] == 'Y')             $privilegeCategories['Administration'][] = "process";
        if($row['Reload_priv'] == 'Y')              $privilegeCategories['Administration'][] = "reload";
        if($row['Shutdown_priv'] == 'Y')            $privilegeCategories['Administration'][] = "shutdown";
        if($row['Show_db_priv'] == 'Y')             $privilegeCategories['Administration'][] = "show databases";
        if($row['Lock_tables_priv'] == 'Y')         $privilegeCategories['Administration'][] = "lock tables";
        if($row['References_priv'] == 'Y')          $privilegeCategories['Administration'][] = "references";
        if($row['Repl_client_priv'] == 'Y')         $privilegeCategories['Administration'][] = "replication client";
        if($row['Repl_slave_priv'] == 'Y')          $privilegeCategories['Administration'][] = "replication slave";
        if($row['Create_user_priv'] == 'Y')         $privilegeCategories['Administration'][] = "create user";

        return $privilegeCategories;
    }

    /**
     * Used to change a permissions array to a list of key => value (column name
     * => column value) pairs to a printable format. These will be placed in an
     * unassociated array for printing.
     * @param array $permissions array full list of pairs to print, for both 'pma' and
     *                           'mysql' user databases.
     *
     * @return array An array, where each value contains an unassociated array
     *               of values formatted for printing.
     */
    private function prettifyPermissions($permissions) : array {
        $output = ['pma', 'mysql'];
        // iterate through pma permissions
        foreach ($permissions['pma'] as $permKey => $permValue) {
            if ($permValue == true) {
                $pmaPerm = preg_replace(
                    '$(?:db|server|table)',
                    '',
                    $permKey
                );
                $pmaPerm = preg_replace(
                    '_([a-z])?',
                    ' \\U$1',
                    $pmaPerm
                );
                $output['pma'] += $pmaPerm;
            }
        }
        // iterate through mysql permissions
        foreach ($permissions['mysql'] as $permKey => $permValue) {
            if ($permValue == true) {
                $mysqlPerm = preg_replace(
                    '_priv^',
                    '',
                    $permKey
                );
                $mysqlPerm = preg_replace(
                    '_([a-z])?',
                    ' \\U$1',
                    $mysqlPerm
                );
                $mysqlPerm = preg_replace(
                    '$([a-z])',
                    '\\U$1',
                    $mysqlPerm
                );
                $output['mysql'] += $mysqlPerm;
            }
        }
        return $output;
    }
}