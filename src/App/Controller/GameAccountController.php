<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\GameAccount;
use Exception;

class GameAccountController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function Create(Request $request, Response $response, array $args)
    {
        try {
            $gameAccount = new GameAccount('');
            $gameAccount->Create($_POST['username'], $_POST['password']);
            $_SESSION['account'] = $gameAccount;

            return $this->container->get('renderer')->render($response, 'page-create-account-success.phtml');
        } catch (Exception $e) {
            return $this->container->get('renderer')->render($response, 'page-create-account-error.phtml', ['message' => $e->getMessage()]);
        }

        return $this->container->get('renderer')->render($response, 'page-create-account.phtml');
    }

    public function Login(Request $request, Response $response, array $args)
    {
        if (isset($_SESSION['account'])) {
            return $response->withRedirect($_SESSION['nextpage']);
        }

        if (isset($_POST['username']) && isset($_POST['password'])) {
            try {
                $gameAccount = new GameAccount('');
                $gameAccount->Login($_POST['username'], $_POST['password']);

                $_SESSION['account'] = $gameAccount;

                return $response->withRedirect($_SESSION['nextpage']);
            } catch (Exception $e) {
                return $this->container->get('renderer')->render($response, 'page-login.phtml', [
                        'title' => 'Login Failure',
                        'message' => $e->getMessage(),
                    ]);
            }
        } else {
            if (isset($_SESSION['account'])) {
                return $response->withRedirect($_SESSION['nextpage']);
            }

            return $this->container->get('renderer')->render($response, 'page-login.phtml', ['nextpage' => 'login']);
        }
    }

    public function Logout(Request $request, Response $response, array $args)
    {
        session_unset();
        session_destroy();

        return $response->withRedirect('./');
    }

    public function ChangePassword(Request $request, Response $response, array $args)
    {
        if (!isset($_SESSION['account'])) {
            $_SESSION['nextpage'] = 'manage';

            return $response->withRedirect('login');
        }

        $result = $_SESSION['account']->ChangePassword($_POST['password']);

        return $this->container->get('renderer')->render($response, 'page-generic-message.phtml', [
            'title' => 'Success',
            'message' => 'Successfully Changed Password', ]);
    }
}
