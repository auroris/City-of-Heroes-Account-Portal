<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

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
    }

    public function Report(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        AdminController::VerifyLogin($HttpResponse);
    }
}
