<?php

use Slim\App;
use App\Controller\StaticController;
use App\Controller\GameAccountController;
use App\Controller\CharacterTransferController;
use App\Controller\FederationController;
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
        $app->post('/character', CharacterTransferController::class.':PutCharacter');
        $app->get('/character/{encrypted_name}/[{type}]', CharacterTransferController::class.':GetCharacter');
        $app->delete('/character/{id}', CharacterTransferController::class.':DeleteCharacter');

        $app->post('/account', AccountTransferController::class.':CreateOrUpdateAccount');
        $app->get('/account/character-list/{encrypted_name}', AccountTransferController::class.':ListCharacters');
    });

    $app->group('/federation', function (App $app) {
        $app->get('/login', FederationController::class.':Login');
        $app->post('/transfer-character-request', FederationController::class.':TransferCharacterRequest');
        $app->get('/pull-character', FederationController::class.':PullCharacter');
    });
};
