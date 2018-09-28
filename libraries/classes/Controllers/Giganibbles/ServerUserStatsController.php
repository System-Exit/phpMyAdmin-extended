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
        $relation = new Relation();

        // Get user information
        $info_list = [
            'name'      => $cfg['Server']['user'],
            // TODO: Get date of user creation
            'date'      => 'None',
            'server'    => $cfg['Server']['host']
        ];
        // Get user permissions
        //$query = "SELECT mysql.db";
        //$rawPermissions;
        $permissions = [
            'pma'       => $this->getUserPmaPrivs($cfg['Server']['user']),
            'mysql'     => []
        ];
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
     * Accesses user permissions from the mysql database. Use this to append to
     * the total permission list.
     * @return array An array of all values to be added. All values are returned
     *               as key => value pairs, where the column name is the key and
     *               the column value is the value.
     */
    private function getUserMySqlPrivs() : array {
        return null;
    }

    /**
     * Accesses user permissions from the PMA usergroups list. This data is
     * appended to the permissions list.
     * @param string $user username of user to get user group permissions from
     * @return array An array of all values to be added. All values are returned
     *               as key => value pairs, where the column name is the key and
     *               the column value is the value.
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
            . "WHERE usergroup = '$usergroup'";
        $result = $relation->queryAsControlUser($query);
        // Parse through server, database and table level tab permissions
        $privileges = [];
        while($row = $result->fetch_assoc())
        {
            // Skip row if privilege is not allowed
            if($row['allowed'] == 'N') continue;

            // Determine privilege category and store it in privileges array
            if(strpos($row['tab'], "server_") !== false)
                $privileges['Server'][] = explode('server_', $row['tab'])[1];
            if(strpos($row['tab'], "db_") !== false)
                $privileges['Database'][] = explode('db_', $row['tab'])[1];
            if(strpos($row['tab'], "table_") !== false)
                $privileges['Table'][] = explode('table_', $row['tab'])[1];
        }
        // Build strings for each category that has a valid permission and adds it to privileges strings
        // Each privilege has all '_' replaces with spaces for readability and is separated by commas
        if(isset($privileges['Server']))
        {
            $catToAdd = "Server: ";
            for($i = 0; $i < count($privileges['Server']); $i++)
            {
                $catToAdd .= str_replace("_", " ", $privileges['Server'][$i]).", ";
            }
            $catToAdd = rtrim($catToAdd, ", ");
            $privilegesStrings[] = $catToAdd;
        }
        if(isset($privileges['Database']))
        {
            $catToAdd = "Database: ";
            for($i = 0; $i < count($privileges['Database']); $i++)
            {
                $catToAdd .= str_replace("_", " ", $privileges['Database'][$i]).", ";
            }
            $catToAdd = rtrim($catToAdd, ", ");
            $privilegesStrings[] = $catToAdd;
        }
        if(isset($privileges['Table']))
        {
            $catToAdd = "Table: ";
            for($i = 0; $i < count($privileges['Table']); $i++)
            {
                $catToAdd .= str_replace("_", " ", $privileges['Table'][$i]).", ";
            }
            $catToAdd = rtrim($catToAdd, ", ");
            $privilegesStrings[] = $catToAdd;
        }

        // Return all valid built strings for privileges
        return $privilegesStrings;
    }

    /**
     * Convenience method for getting the usergroup of the current user.
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