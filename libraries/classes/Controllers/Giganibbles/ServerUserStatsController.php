<?php
/**
 * Created by PhpStorm.
 * User: lukel
 * Date: 18/08/08
 * Time: 4:58 PM
 */
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
        $this->response->addHTML($this->template->render(
            'Giganibbles/server_user_stats',
            [
                'info_list'     => [
                    'name'      => 'Test',
                    'date'      => '23/09/2018',
                    'server'    => 'localhost'
                ],
                'permissions'   => [
                    print_r($this->getUserPmaPrivs())
                ],
                'usage_list'    => [
                    ['type' => 'test1', 'value' => 'Hello!'],
                    ['type' => 'test2', 'value' => 'Goodbye!']
                ]
            ]
        ));
    }

    /**
     * Accesses user permissions from the mysql database. Use this to append to
     * the total permission list.
     * @return array
     */
    private function getUserMySqlPrivs() : array {
        global $cfg;
        $relation = new Relation();
        $user = $cfg['Server']['user'];
        $host = $cfg['Server']['host'];
        $output = [];
        $select =
            'db.select_priv, ' .
            'db.insert_priv, ' .
            'db.update_priv, ' .
            'db.delete_priv, ' .
            'db.create_priv, ' .
            'db.drop_priv, ' .
            'db.grant_priv, ' .
            'db.references_priv, ' .
            'db.index_priv, ' .
            'db.alter_priv, ' .
            'db.create_tmp_table_priv, ' .
            'db.lock_tables_priv, ' .
            'db.create_view_priv, ' .
            'db.index_priv, ';
        $queryTables =
            'SELECT (' .
            ') ' .
            'FROM mysql.user db ' .
            'AND db.user LIKE \'' . $user . '\';';
        $resultTables = $relation->queryAsControlUser($queryTables);
        if ($resultTables !== false && $resultTables->num_rows > 0) {
            while ($row = $resultTables->fetch_assoc()) {
                if ($row[''])
                $output['tables_priv'];
            }
        }
    }

    private function getUserPmaPrivs() : array {
        $relation = new Relation();
        $output = [];
        $usergroup = $this->getUserGroupOfUser();
        if (empty($usergroup)) {
            return $output;
        }
        $query = 'SELECT * FROM phpmyadmin.pma__usergroups db ' .
                 'WHERE db.usergroup LIKE \'' . $usergroup . '\';';
        $result = $relation->queryAsControlUser($query, true);
        var_dump($result);
        if ($result !== false && $result->row_num > 0) {
            $output = array_merge($output, $result);
//            while ($row = $result->fetch_assoc()) {
//                $row
//            }
        }
        return $output;

    }

    private function getUserGroupOfUser() {
        global $cfg;
        $relation = new Relation();
        $user = $cfg['Server']['user'];
        $query = 'SELECT db.usergroup FROM phpmyadmin.pma__users db ' .
                 'WHERE db.username LIKE \'' . $user . '\';';
        $result = $relation->queryAsControlUser($query, true);
        var_dump($result);
        if ($result !== false && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['usergroup'])) {
                return $row['usergroup'];
            }
        }
        return null;
    }

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
    }

}