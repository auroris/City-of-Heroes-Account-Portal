<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use  Slim\Http\Request;
use  Slim\Http\Response;

class StaticController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function Home(Request $request, Response $response, array $args)
    {
        $gameStats = new \App\Model\CoHStats();

        return $this->container->get('renderer')->render($response, 'page-index.phtml', [
            'accounts' => $gameStats->CountAccounts(),
            'characters' => $gameStats->CountCharacters(),
            'status' => $gameStats->GetServerStatus()
        ]);
    }

    public function Create(Request $request, Response $response, array $args)
    {
        return $this->container->get('renderer')->render($response, 'page-create-account.phtml');
    }

    public function Manage(Request $request, Response $response, array $args)
    {
        if (!isset($_SESSION['account'])) {
            $_SESSION['nextpage'] = 'manage';

            return $response->withRedirect('login');
        }

        return $this->container->get('renderer')->render(
            $response,
            'page-manage.phtml',
            ['username' => $_SESSION['account']->GetUsername(),
            'characters' => $_SESSION['account']->GetCharacterList(),
            'federation' => $GLOBALS['federation'],
            'host' => $_SERVER['HTTP_HOST'],
            ]
        );
    }
}
