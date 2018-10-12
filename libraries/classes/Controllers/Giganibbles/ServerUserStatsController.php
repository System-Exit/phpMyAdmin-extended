<?php
/**
 * Message controller for messages page
 *
 * @package PhpMyAdmin\Controllers\Giganibbles
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Giganibbles;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Message;
use TCPDF;

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
     * @throws \Exception
     */
    public function indexAction()
    {
        global $cfg;

        $user = $cfg['Server']['user'];
        $host = $cfg['Server']['host'];

        // Check if generating report, generate report for user if so
        if(isset($_GET['export']))
        {
            $this->generateUserStatReportPDF();
        }

        /**
         * Incrementation of statistics page visits if user is logged in
         */
        ServerUserStatsController::incrementPageView("user_stats");

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
        $usage_list = $this->getUserUsage($user);

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

        // Get the permissions for the user, also checking for user with '%' host
        $query = "SELECT * "
            . "FROM mysql.user "
            . "WHERE user = '$user' "
            . "AND host = '$host' "
            . "OR host = '%'; ";
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

    /**
     * Access usages statistics for specified user from userstats table
     *
     * @param string $user Username of user to get usage statistics for
     *
     * @return array Array of usage statistics in the form of 'type'/'value' pairs
     */
    private function getUserUsage($user)
    {
        $relation = new Relation();
        $usage = [];

        // Get all usage statistics
        $query = "SELECT * "
            . "FROM `phpmyadmin`.`pma__userstats` "
            . "WHERE user = '$user';";
        $result = $relation->queryAsControlUser($query);
        $row = $result->fetch_assoc();

        // Build array of page view stats
        foreach($row as $type => $value)
        {
            // Don't include username
            if($type == "user") continue;

            // Add usage type and value to array
            $type = str_replace("_", " ", $type);
            $usage[] = ['type' => $type, 'value' => $value];
        }

        return $usage;
    }

    /**
     * Method for recording a given page visit for current user
     *
     * @param string $page Name of page to increment view count for
     *
     * @return bool Whether or not the recording was successful
     */
    static public function incrementPageView($page)
    {
        global $cfg;
        $relation = new Relation();

        $user = $cfg['Server']['user'];
        $statistic_name = $page . "_page_views";

        // Check if user exists in statistics
        ServerUserStatsController::verifyUserStatsIsRecorded($user);

        // Check that given page statistic exists, returning false if not
        $query = "SHOW COLUMNS "
            . "FROM `phpmyadmin`.`pma__userstats` "
            . "LIKE '$statistic_name';";
        $result = $relation->queryAsControlUser($query);
        if($result->num_rows == 0) return false;

        // Increment page views value
        $query = "UPDATE `phpmyadmin`.`pma__userstats` "
            . "SET $statistic_name = $statistic_name + 1 "
            . "WHERE user = '$user';";
        $relation->queryAsControlUser($query);

        // Return success
        return true;
    }

    /**
     * Checks if user exists in statistics page, creating an entry for them if not
     *
     * @param string $user Username of user to check is in stats table
     *
     * @return void
     */
    static private function verifyUserStatsIsRecorded($user)
    {
        $relation = new Relation();

        // Check if user exists in statistics
        $query = "SELECT * "
            . "FROM `phpmyadmin`.`pma__userstats` "
            . "WHERE user = '$user';";
        $result = $relation->queryAsControlUser($query);

        // Create an entry for the user they were not found in statistics
        if($result->num_rows == 0)
        {
            $query = "INSERT INTO `phpmyadmin`.`pma__userstats` (`user`) VALUES ('$user');";
            $relation->queryAsControlUser($query);
        }
        // Simply return if the user is already recorded in statistics
        else return;
    }

    /**
     * Exports statistics as a PDF report
     * Builds the report using TCPDF
     *
     * @return void
     * @throws \Exception When save directory isn't defined or cant be accessed
     */
    public function generateUserStatReportPDF()
    {
        global $cfg;
        $this->response;

        $user = $cfg['Server']['user'];
        $host = $cfg['Server']['host'];
        $save_dir = $cfg['Server']['user_reports_directory'];

        // Check if save directory is set and can be accessed
        // Display errors and return if either is true
        if($save_dir == '')
        {
            $msg = Message::ERROR(__("Directory to save reports has not been defined. "
                . "You can do so in the 'config.inc.php' file."));
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $msg);
            return;
        }
        else if(!is_writable($save_dir))
        {
            $msg = Message::ERROR(__("Directory for saving reports is not writable."));
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $msg);
            return;
        }

        // Construct PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION,
            PDF_UNIT,
            PDF_PAGE_FORMAT,
            true,
            'UTF-8',
            false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor("phpMyAdmin");
        $pdf->SetTitle("Statistics report for $user");
        $pdf->SetSubject("Statistics report");
        $pdf->setHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
        $pdf->setFooterData('', '');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(true, 10);
        $pdf->setFont('helvetica', '', 12);

        // Create PDF page
        $pdf->AddPage();
        // Define style
        $heading_Style = "text-decoration: underline;";
        // Build HTML for report
        $content = "<h1 style=\"{$heading_Style}\">Statistics report for {$user}</h1>";
        $content .= "<br/>";
        // User info section
        $content .= "<h2>User Information</h2>";
        $content .= "<p>Name: {$user}</p>";
        $content .= "<p>Date Created: none</p>";
        $content .= "<p>Server: {$host}</p>";
        $content .= "<br/>";
        // Permissions section
        $content .= "<h2>User Permissions</h2>";
        $content .= "<h3>PHP MY ADMIN:</h3>";
        foreach($this->getUserPmaPrivs($user) as $row)
        {
            $content .= "<p>{$row}</p>";
        }
        $content .= "<h3>MYSQL:</h3>";
        foreach($this->getUserMySqlPrivs($user, $host) as $row)
        {
            $content .= "<p>{$row}</p>";
        }
        $content .= "<br/>";
        // Usage info section
        $content .= "<h2>User usage statistics</h2>";
        foreach($this->getUserUsage($user) as $statistic)
        {
            $content .= "<p>{$statistic['type']}: {$statistic['value']}</p>";
        }

        // Write HTML to PDF
        $pdf->writeHTML($content);
        $pdf->Close();

        // Have PDF generated and stored in specified location with generated name
        // TODO: Get downloading of file working
        $save_path = "{$save_dir}/{$user}_statistic_report.pdf";
        if($pdf->Output($save_path, 'F') == '')
        {
            $msg = Message::SUCCESS(__("Report saved in {$save_dir}"));
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $msg);
        }
        else
        {
            $msg = Message::ERROR(__("Report failed to save in {$save_dir}"));
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $msg);
        }

        return;
    }
}