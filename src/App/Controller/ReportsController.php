<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Util\SqlServer;

class ReportsController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function ListReports(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        AdminController::VerifyLogin($HttpResponse);

        include $GLOBALS['ROOT'].'/config/reports.default.php';
        $file = $GLOBALS['ROOT'].'/config/reports.user.php';
        file_exists($file) and include $file;

        return $this->container->get('renderer')->render($HttpResponse, 'core/page-reports.phtml', ['reports' => $reports]);
    }

    // Replacement strings: '@ACCOUNT_NAME', '@CHARACTER_NAME', '@ACCOUNT_UID', '@CHARACTER_CID'
    public function Report(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        // Check permission
        AdminController::VerifyLogin($HttpResponse);

        // Includes
        include $GLOBALS['ROOT'].'/config/reports.default.php';
        $file = $GLOBALS['ROOT'].'/config/reports.user.php';
        file_exists($file) and include $file;

        // Define vars
        $sql = SqlServer::GetInstance();
        $query = $reports[$HttpArgs['name']]['sql'];
        $accounts = [];
        $characters = [];
        $params = [];

        if (false !== strpos($query, '@ACCOUNT_NAME') || false !== strpos($query, '@ACCOUNT_UID')) {
            $accounts = $sql->FetchNumeric('SELECT user_account.uid as uid, user_account.account as account_name FROM cohauth.dbo.user_account ORDER BY account');

            if (false !== strpos($query, '@ACCOUNT_NAME')) {
                foreach ($accounts as $row) {
                    if ($row[0] == $_GET['account']) {
                        $query = 'DECLARE @ACCOUNT_NAME VARCHAR(MAXLEN) = ?;'.$query;
                        array_push($params, $row[1]);
                    }
                }
            }

            if (false !== strpos($query, '@ACCOUNT_UID')) {
                $query = 'DECLARE @ACCOUNT_UID INT = ?;'.$query;
                array_push($params, $_GET['account']);
            }
        }

        if (false !== strpos($query, '@CHARACTER_NAME') || false !== strpos($query, '@CHARACTER_CID')) {
            if (isset($_GET['account'])) {
                $characters = $sql->FetchNumeric('SELECT Ents.ContainerId, Ents.Name FROM cohdb.dbo.Ents ORDER BY Name WHERE AuthId = ?', array($_GET['account']));
            }

            if (false !== strpos($query, '@CHARACTER_NAME')) {
                foreach ($characters as $row) {
                    if ($row[0] == $_GET['character']) {
                        $query = 'DECLARE @ACCOUNT_NAME VARCHAR(MAXLEN) = ?;'.$query;
                        array_push($params, $row[1]);
                    }
                }
            }

            if (false !== strpos($query, '@CHARACTER_CID')) {
                $query = 'DECLARE @CHARACTER_CID INT = ?;'.$query;
                array_push($params, $_GET['character']);
            }
        }

        $results = $sql->FetchAssoc($query, $params);

        if (isset($reports[$HttpArgs['name']]['transpose']) && true == $reports[$HttpArgs['name']]['transpose']) {
            $results = $this->Transpose($results);
        }

        return $this->container->get('renderer')->render($HttpResponse, 'core/page-reports-display.phtml', [
            'reports' => $reports,
            'results' => $results,
            'title' => $HttpArgs['name'],
            'accounts' => $accounts,
            'characters' => $characters,
        ]);
    }

    // Manual pivot, when you don't want to make SQL Server do 415 column pivots (looking at you, salvage tables)
    private function Transpose($assocArray)
    {
        $keys = array_keys($assocArray[0]);
        $transposedrow = [];

        // Set up the first column
        for ($col = 1; $col < count($keys); ++$col) {
            $transposed[$col - 1] = [$keys[0] => $keys[$col]];
        }

        // Do additional columns
        for ($col = 1; $col < count($keys); ++$col) {
            for ($row = 0; $row < count($assocArray); ++$row) {
                $transposed[$col - 1][$assocArray[$row][$keys[0]]] = $assocArray[$row][$keys[$col]];
            }
        }

        return $transposed;
    }
}
