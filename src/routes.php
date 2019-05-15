<?php

use Slim\App;
use App\Controller\StaticController;
use App\Controller\GameAccountController;
use App\Controller\FederationController;
use App\Controller\APIController;
use App\Util\MonoLogger;

return function (App $app) {
    $container = $app->getContainer();
    MonoLogger::SetLogger($container->get('logger'));

    $app->get('/', StaticController::class.':Home');

    $app->group('', function (App $app) {
        $app->get('/create', StaticController::class.':Create');
        $app->get('/manage', StaticController::class.':Manage');

        $app->get('/login', GameAccountController::class.':Login');
        $app->post('/login', GameAccountController::class.':Login');
        $app->get('/logout', GameAccountController::class.':Logout');
        $app->post('/create', GameAccountController::class.':Create');
        $app->post('/changepassword', GameAccountController::class.':ChangePassword');
    })->add($container->get('csrf'));

    $app->group('/api', function (App $app) {
        // CORS headers
        $app->options('/{routes:.+}', function ($request, $response, $args) {
            return $response;
        });
        $app->add(function ($req, $res, $next) {
            $response = $next($req, $res);

            return $response
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        });

        $app->get('/character/{type}', APIController::class.':GetCharacter');
        $app->get('/stats/{type}', APIController::class.':GetServerStats');

        // 404'd if anything else
        $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($req, $res) {
            $handler = $this->notFoundHandler;

            return $handler($req, $res);
        });
    });

    $app->group('/federation', function (App $app) {
        $app->get('/login', FederationController::class.':Login');
        $app->post('/transfer-character-request', FederationController::class.':TransferCharacterRequest');
        $app->get('/pull-character', FederationController::class.':PullCharacter');
    });
};
