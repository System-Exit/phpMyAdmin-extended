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

        // get all necessary data
        $info_list = [
            'name'      => 'None',
            'date'      => 'None',
            'server'    => 'None'
        ];
        $permissions = ['pma' => [], 'mysql' => []];
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
     * @return array An array of all values to be added. All values are returned
     *               as key => value pairs, where the column name is the key and
     *               the column value is the value.
     */
    private function getUserPmaPrivs() : array {
        return null;
    }

    /**
     * Convenience method for getting the usergroup of the current user.
     * @return mixed A usergroup for the current user, or null if the user has
     *               not been assigned a usergroup.
     */
    private function getUserGroupOfUser() {
        return null;
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