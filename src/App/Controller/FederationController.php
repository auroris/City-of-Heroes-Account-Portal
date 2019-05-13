<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\GameAccount;
use App\Messages\Message;
use App\Util\Http;
use App\Model\Character;
use Exception;

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
        try {
            $fedServer = $this->FindFederationServerByName($_SESSION['pullcharacter']['from']);
            $rawData = Http::Get($fedServer['Url'].'/api/character/raw?q='.$_SESSION['pullcharacter']['character']);
            $character = new Character();
            $character->SetArray(explode("\n", $rawData));

            // Apply to the correct account
            $character->AuthId = $_SESSION['account']->GetUID();
            $character->AuthName = $_SESSION['account']->GetUsername();

            // Apply the AllowTransfers policy
            if (isset($fedServer['Policy']['AllowTransfers']) && false === $fedServer['Policy']['AllowTransfers']) {
                throw new Exception('Character transfer failed: Policy on this system forbids characters originating on '.$fedServer['Name']);
            }

            // Apply the ForceInfluence policy
            if (isset($fedServer['Policy']['ForceInfluence']) && false !== $fedServer['Policy']['ForceInfluence']) {
                $character->InfluencePoints = $fedServer['Policy']['ForceInfluence'];
            }

            // Apply rhe ForceAccessLevel policy
            if (isset($fedServer['Policy']['ForceAccessLevel']) && false !== $fedServer['Policy']['ForceAccessLevel']) {
                if (isset($character->AccessLevel)) { //AccessLevel is not currently specified in our exports, so it defaults to null right now
                    $character->AccessLevel = $fedServer['Policy']['ForceAccessLevel'];
                }
            }

            // Apply the AllowInventory policy
            if (isset($fedServer['Policy']['AllowInventory']) && false === $fedServer['Policy']['AllowInventory']) {
                unset($character->InvSalvage0);
                unset($character->InvRecipeInvention);
            }

            $character->PutCharacter();

            return $this->container->get('renderer')->render($HttpResponse, 'page-generic-message.phtml', ['title' => 'Welcome to '.getenv('portal_name'), 'message' => $character->Name.' has been transferred successfully!']);
        } catch (Exception $e) {
            return $this->container->get('renderer')->render($HttpResponse, 'page-generic-message.phtml', ['title' => 'An Error was encountered', 'message' => $e->GetMessage()]);
        }
    }

    // Find a federation server by its name.
    private function FindFederationServerByName($name)
    {
        foreach ($GLOBALS['federation'] as $item) {
            if (false !== stripos($item['Name'], $name)) {
                return $item;
            }
        }

        throw new Exception('Unable to locate federated server by name: '.$name.'. Please ensure that /src/Config/federation.php has an entry for '.$name.'.');
    }
}
