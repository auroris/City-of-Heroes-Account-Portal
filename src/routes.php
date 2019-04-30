<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use App\CoH\GameData;
use App\CoH\GameAccount;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {
        // Render index view
        return $container->get('renderer')->render($response, 'page-index.phtml', [
            'accounts' => GameData::CountAccounts(),
            'characters' => GameData::CountCharacters(),
            'online' => GameData::CountOnline()
        ]);
    });
    
    $app->get('/create', function (Request $request, Response $response, array $args) use ($container) {
        return $container->get('renderer')->render($response, 'page-create-account.phtml');
    });
    
    $app->post('/create', function (Request $request, Response $response, array $args) use ($container) {
        $gameAccount = new GameAccount();
        $result = $gameAccount->Create($_POST["username"], $_POST["password"], $container->get('logger'));
        
        if ($result['success'] == true) {
            $_SESSION["account"] = $gameAccount;
            return $container->get('renderer')->render($response, 'page-create-account-success.phtml', ['message' => $result['message']]);
        }
        else {
            return $container->get('renderer')->render($response, 'page-create-account-error.phtml', ['message' => $result['message']]);
        }
    });
    
    $app->get('/login', function (Request $request, Response $response, array $args) use ($container) {
        if (isset($_SESSION["account"])) { return $response->withRedirect('./manage'); }
        return $container->get('renderer')->render($response, 'page-login.phtml');
    });
    
    $app->post('/login', function (Request $request, Response $response, array $args) use ($container) {
        $gameAccount = new GameAccount();
        $result = $gameAccount->Login($_POST["username"], $_POST["password"], $container->get('logger'));
        
        if ($result['success'] == true) {
            $_SESSION["account"] = $gameAccount;
            return $response->withRedirect('./manage'); 
        }
        else {
            return $container->get('renderer')->render($response, 'page-login.phtml', ['message' => $result['message']]);
        }
    });
    
    $app->get('/manage', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["account"])) { return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]); }
        return $container->get('renderer')->render($response, 'page-manage.phtml', ['username' => $_SESSION["account"]->GetUsername(), 'characters' => $_SESSION["account"]->GetCharacterList()]);
    });
    
    $app->post('/changepassword', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["account"])) { return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]); }
        
        $result = $_SESSION["account"]->ChangePassword($_POST["password"]);
        return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => $result['success'] ? "Successfully Changed Password" : "Error", $result['message']]);
    });
    
    $app->get('/logout', function (Request $request, Response $response, array $args) use ($container) {
        session_unset();
        session_destroy();
        return $response->withRedirect('./');
    });
};
