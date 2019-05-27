<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class StaticController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function Home(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        $gameStats = new \App\Model\CoHStats();

        return $this->container->get('renderer')->render($HttpResponse, 'core/page-index.phtml', [
            'accounts' => $gameStats->CountAccounts(),
            'characters' => $gameStats->CountCharacters(),
            'status' => $gameStats->GetServerStatus(),
        ]);
    }

    public function Create(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        return $this->container->get('renderer')->render($HttpResponse, 'core/page-create-account.phtml');
    }

    public function Manage(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        if (!isset($_SESSION['account'])) {
            $_SESSION['nextpage'] = 'manage';

            return $HttpResponse->withRedirect('login');
        }

        return $this->container->get('renderer')->render(
            $HttpResponse,
            'core/page-manage.phtml',
            [
                'username' => $_SESSION['account']->GetUsername(),
                'characters' => $_SESSION['account']->GetCharacterList(),
                'lockedcharacters' => $_SESSION['account']->GetLockedCharacters(),
                'federation' => $GLOBALS['federation'],
            ]
        );
    }

    public function Page(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        try {
            return $this->container->get('renderer')->render(
                $HttpResponse,
                $HttpArgs['page'].'.phtml'
            );
        } catch (Exception $e) {
            //404'd
            $handler = $this->container->notFoundHandler;

            return $handler($HttpRequest, $HttpResponse);
        }
    }
}
