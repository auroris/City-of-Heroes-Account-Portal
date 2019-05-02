<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use App\CoH\GameData;
use App\CoH\GameAccount;
use App\Coh\Federation;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {
        // Render index view
        return $container->get('renderer')->render($response, 'page-index.phtml', [
            'accounts' => GameData::countAccounts(),
            'characters' => GameData::countCharacters(),
            'online' => GameData::countOnline()
        ]);
    });
    
    $app->get('/create', function (Request $request, Response $response, array $args) use ($container) {
        return $container->get('renderer')->render($response, 'page-create-account.phtml');
    });
    
    $app->post('/create', function (Request $request, Response $response, array $args) use ($container) {
        $gameAccount = new GameAccount();
        $result = $gameAccount->create($_POST["username"], $_POST["password"], $container->get('logger'));
        
        if ($result['success'] == true) {
            $_SESSION["account"] = $gameAccount;
            return $container->get('renderer')->render($response, 'page-create-account-success.phtml', ['message' => $result['message']]);
        }
        else {
            return $container->get('renderer')->render($response, 'page-create-account-error.phtml', ['message' => $result['message']]);
        }
    });
    
    /********** Login Logic */
    $app->get('/login', function (Request $request, Response $response, array $args) use ($container) {
        $nextpage = "manage";
        if (isset($_SESSION['nextpage'])) { $nextpage = $_SESSION['nextpage']; }
        if (isset($_SESSION["account"])) { return $response->withRedirect($nextpage); }
        return $container->get('renderer')->render($response, 'page-login.phtml', ['nextpage' => 'login']);
    });
    
    $app->post('/login', function (Request $request, Response $response, array $args) use ($container) {
        $nextpage = "manage";
        if (isset($_SESSION['nextpage'])) { $nextpage = $_SESSION['nextpage']; }
        if (isset($_SESSION["account"])) { return $response->withRedirect($nextpage); }

        $gameAccount = new GameAccount();
        $result = $gameAccount->login($_POST["username"], $_POST["password"], $container->get('logger'));
        
        if ($result['success'] == true) {
            $_SESSION["account"] = $gameAccount;
            return $response->withRedirect($nextpage); 
        }
        else {
            return $container->get('renderer')->render($response, 'page-login.phtml', [
                'title' => 'Login Failure', 
                'message' => $result['message'], 
                'nextpage' => $_POST['nextpage']
            ]);
        }
    });
    
    $app->get('/manage', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["account"])) { return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]); }
        return $container->get('renderer')->render($response, 'page-manage.phtml', 
            ['username' => $_SESSION["account"]->GetUsername(), 
            'characters' => $_SESSION["account"]->GetCharacterList($container->get('logger')),
            'federation' => $GLOBALS["federation"],
            'host' => $_SERVER['HTTP_HOST']
            ]);
    });

    $app->post('/changepassword', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["account"])) { return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]); }
        
        $result = $_SESSION["account"]->ChangePassword($_POST["password"], $container->get('logger'));
        return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => $result['success'] === true ? "Successfully Changed Password" : "Error", 'message' => $result['message']]);
    });
    
    $app->get('/logout', function (Request $request, Response $response, array $args) use ($container) {
        session_unset();
        session_destroy();
        return $response->withRedirect('./');
    });

    $app->get('/character', function (Request $request, Response $response, array $args) use ($container) {
        $newResponse = $response->withHeader('Content-type', 'text/plain');
        return $newResponse->write(implode("\n", GameAccount::getCharacter($_GET['id'])));
    });

    $app->post('/redirect', function (Request $request, Response $response, array $args) use ($container) {
        return $response->withRedirect($_POST["url"]);
    });

    /********** Federation logic */
    $app->get('/federation', function (Request $request, Response $response, array $args) use ($container) {
        // Guest came here straight from the foreign server, get information about the server they came from
        // If they are already logged in, send them to the select characters form. If not, ask them to login.
        $_SESSION['federation'] = new Federation($_GET["server"]);

        if (isset($_SESSION["account"])) { return $response->withRedirect('federation-select-character'); }
        $_SESSION['nextpage'] = 'federation-select-character';
        return $response->withRedirect('login');
    });

    $app->get('/federation-select-character', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["account"])) { return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]); }
        $federation = $_SESSION["federation"];
        //unset($_SESSION["federation"]);
        unset($_SESSION["nextpage"]);

        return $container->get('renderer')->render($response, 'page-federation-select-character.phtml', [
            'federation_url' => $federation->getUrl(),
            'federation_name' => $federation->getName(),
            'characters' => $_SESSION["account"]->GetCharacterList($container->get('logger')),
            'host' => $_SERVER['HTTP_HOST']
            ]
        );
    });

    $app->get('/import-character', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["account"])) { return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]); }
        $_SESSION['federation'] = new Federation($_GET["server"]);
        echo print_r(file_get_contents($_SESSION['federation']->getUrl() . '/character?id=' . urlencode($_GET["id"])));
    });
};