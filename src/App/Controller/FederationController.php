<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use App\Util\Http;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\GameAccount;
use App\Model\Character;
use App\Messages\Message;

class FederationController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function TransferCharacterRequest(Request $request, Response $response, array $args)
    {
        $fedServer = $this->FindFederationServerByName($_POST['server']);
        $accountCheck = new Message($_POST['server']);
        $accountCheck->username = $_SESSION['account']->GetUsername();
        $accountCheck->password = $_SESSION['account']->GetPassword();
        $responseAccountCheck = new Message();
        $responseAccountCheck->Unserialize(Http::Post($fedServer['Url'].'/federation/transfer-check', ['message' => json_encode($accountCheck)]));

        if ('Ok' == $responseAccountCheck->status) {
            //$responseAccountCheck->policy holds the remote server's policy regarding characters coming from this system

            $character = new Character(DataHandling::Decrypt($_POST['character'], $GLOBALS['crypto']['key'], $GLOBALS['crypto']['iv']));
            $transmit = new Message($_POST['server']);
            $transmit->character = $character->ToJSON();
            $responseTransmit = new Message();
            $responseTransmit->Unserialize(Http::Post($fedServer['Url'].'/federation/transfer-character', ['message' => json_encode($transmit)]));
        }
    }

    public function TransferCharacterCheck(Request $request, Response $response, array $args)
    {
        $message = new Message();
        $message->Unserialize($_POST['message'], true);
        $gameAccount = new GameAccount($message->username);
        $fedServer = $this->FindFederationServerByName($message->from);

        $returnMessage = new Message($message->from);
        $returnMessage->policy = $fedServer['Policy'];

        $newResponse = $response->withHeader('Content-type', 'application/json');
        if ($gameAccount->VerifyHashedPassword($message->password)) {
            $returnMessage->status = 'Ok';
            $newResponse->write(json_encode($returnMessage));
        } else {
            $returnMessage->status = 'Fail';
            $newResponse->write(json_encode($returnMessage));
        }
    }

    public function TransferCharacterUpload(Request $request, Response $response, array $args)
    {
    }

    // Find a federation server by its name.
    private function FindFederationServerByName($name)
    {
        foreach ($GLOBALS['federation'] as $item) {
            if (false !== stripos($item['Name'], $name)) {
                return $item;
            }
        }
    }
}
