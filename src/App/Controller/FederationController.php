<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\GameAccount;
use App\Messages\Message;
use App\Util\Http;
use App\Model\Character;

class FederationController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    // Prepares a message to the destination server and transfers you there to perform the character pull
    public function TransferCharacterRequest(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        $fedServer = $this->FindFederationServerByName($_POST['server']);
        $myUsername = $_SESSION['account']->GetUsername();
        $myPassword = $_SESSION['account']->GetPassword();

        $login = new Message($_POST['server']);
        $login->username = $myUsername;
        $login->password = $myPassword;
        $login->action = 'PullCharacter';
        $login->character = $_POST['character'];

        return $HttpResponse->withRedirect($fedServer['Url'].'/federation/login?message='.urlencode(json_encode($login)));
    }

    public function Login(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        $message = new Message();
        $message->Unserialize($_GET['message']);
        $gameAccount = new GameAccount($message->username);
        if ($gameAccount->VerifyHashedPassword($message->password)) {
            $_SESSION['account'] = $gameAccount;
            $_SESSION['pullcharacter'] = ['character' => $message->character, 'from' => $message->from];

            return $HttpResponse->withRedirect('pull-character');
        } else {
            $_SESSION['nextpage'] = 'federation/pull-character';
            $_SESSION['pullcharacter'] = ['character' => $message->character, 'from' => $message->from];

            return $HttpResponse->withRedirect('../login');
        }
    }

    public function PullCharacter(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        $fedServer = $this->FindFederationServerByName($_SESSION['pullcharacter']['from']);
        $rawData = Http::Get($fedServer['Url'].'/api/character/'.$_SESSION['pullcharacter']['character'].'/raw');
        $character = new Character();
        $character->SetArray(explode("\n", $rawData));

        $character->AuthId = $_SESSION['account']->GetUID();
        $character->Name = 'Aleena3';
        $character->PutCharacter();
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
