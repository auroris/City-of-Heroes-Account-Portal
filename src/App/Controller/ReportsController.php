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
        AdminController::VerifyLogin($HttpResponse);

        include $GLOBALS['ROOT'].'/config/reports.default.php';

        $file = $GLOBALS['ROOT'].'/config/reports.user.php';
        file_exists($file) and include $file;

        $query = $reports[$HttpArgs['name']]['sql'];

        if (false !== strpos($query, '@ACCOUNT_NAME')) {
            $query = "DECLARE @ACCOUNT_NAME VARCHAR(MAXLEN) = 'auroris';".$query;
        }

        if (false !== strpos($query, '@ACCOUNT_UID')) {
            $query = 'DECLARE @ACCOUNT_UID INT = 3;'.$query;
        }

        if (false !== strpos($query, '@CHARACTER_NAME')) {
            $query = "DECLARE @ACCOUNT_NAME VARCHAR(MAXLEN) = 'Aleena';".$query;
        }

        if (false !== strpos($query, '@CHARACTER_CID')) {
            $query = 'DECLARE @CHARACTER_CID INT = 4;'.$query;
        }

        $sql = SqlServer::GetInstance();
        $results = $sql->FetchAssoc($query);

        if (isset($reports[$HttpArgs['name']]['transpose']) && true == $reports[$HttpArgs['name']]['transpose']) {
            $results = $this->Transpose($results);
        }

        return $this->container->get('renderer')->render($HttpResponse, 'core/page-reports-display.phtml', ['reports' => $reports, 'results' => $results, 'title' => $HttpArgs['name']]);
    }

    private function Transpose($assocArray)
    {
        $keys = array_keys($assocArray[0]);
        $transposedrow = [];

        // Set up the first column
        for ($col = 1; $col < count($keys); ++$col) {
            $transposed[$col] = [$keys[0] => $keys[$col]];
        }

        for ($col = 1; $col < count($keys); ++$col) {
            for ($row = 0; $row < count($assocArray); ++$row) {
                $transposed[$col][$assocArray[$row][$keys[0]]] = $assocArray[$row][$keys[$col]];
            }
        }

        //print_r($assocArray[0][$keys[0]]);

        //print_r($transposed);
        //die();

        return $transposed;
    }
}
