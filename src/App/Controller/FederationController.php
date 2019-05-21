<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\GameAccount;
use App\Messages\Message;
use App\Util\Http;
use App\Model\Character;
use App\Util\SqlServer;
use App\Util\DataHandling;
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
        $sql = SqlServer::getInstance();

        // If person is online, tell them to get off first
        if ($_SESSION['account']->IsOnline()) {
            return $this->container->get('renderer')->render($HttpResponse, 'core/page-generic-message.phtml', [
                'title' => 'Log Off First',
                'message' => 'You must log out of the game before you can initiate a character transfer.', ]);
        }

        // Get the character's ContainerId
        $containerId = $sql->FetchNumeric(
            'SELECT ContainerId FROM cohdb.dbo.Ents WHERE Name = ?',
            array(DataHandling::Decrypt(urldecode($_POST['character']), getenv('portal_key'), getenv('portal_iv')))
        );

        // If the character is locked for transfer already, abort
        $isLocked = $sql->ReturnsRows('SELECT AccSvrLock FROM cohdb.dbo.Ents2 WHERE ContainerId = ? AND AccSvrLock IS NOT NULL', array($containerId[0][0]));
        if ($isLocked) {
            return $this->container->get('renderer')->render($HttpResponse, 'core/page-generic-message.phtml', [
                'title' => 'Character Locked',
                'message' => 'This character is locked for transfer. If this is in error, please contact a GM.', ]);
        }

        $fedServer = $this->FindFederationServerByName($_POST['server']);
        $myUsername = $_SESSION['account']->GetUsername();
        $myPassword = $_SESSION['account']->GetPassword();

        $login = new Message($_POST['server']);
        $login->username = $myUsername;
        $login->password = $myPassword;
        $login->action = 'PullCharacter';
        $login->character = $_POST['character'];

        // Lockout the character
        $sql->Query(
            'UPDATE cohdb.dbo.Ents2 SET AccSvrLock = ? WHERE ContainerId = ?',
            array(substr('transfer to '.$fedServer['Name'], 0, 72), $containerId[0][0])
        );

        return $HttpResponse->withRedirect($fedServer['Url'].'/federation/login?message='.urlencode(json_encode($login)));
    }

    // Attempts to log you in automatically; if it fails redirects you to the portal's normal login page. After, character transfer
    // request will proceed.
    public function Login(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        $message = new Message();
        $message->Unserialize($_GET['message']);
        $gameAccount = new GameAccount($message->username);
        if ($gameAccount->VerifyHashedPassword($message->password)) {
            $_SESSION['account'] = $gameAccount;
            $_SESSION['pullcharacter'] = ['character' => $message->character, 'from' => $message->from];

            return $HttpResponse->withRedirect('review-policy');
        } else {
            $_SESSION['nextpage'] = 'federation/review-policy';
            $_SESSION['pullcharacter'] = ['character' => $message->character, 'from' => $message->from];

            return $HttpResponse->withRedirect(getenv('portal_url').'login');
        }
    }

    // Informs the user of this server's incoming character policy.
    public function ReviewPolicy(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        if (!isset($_SESSION['pullcharacter']) || !isset($_SESSION['account'])) {
            throw new Exception('Your session is not correct or has expired.');
        }

        $fedServer = $this->FindFederationServerByName($_SESSION['pullcharacter']['from']);

        return $this->container->get('renderer')->render($HttpResponse, 'core/page-federation-review-policy.phtml', ['Server' => $fedServer]);
    }

    // Pulls the character over and implements all transfer policies.
    public function PullCharacter(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        try {
            if (!isset($_SESSION['pullcharacter']) || !isset($_SESSION['account'])) {
                throw new Exception('Your session is not correct or has expired.');
            }

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
            if (isset($fedServer['Policy']['ForceInfluence']) && -1 !== $fedServer['Policy']['ForceInfluence']) {
                $character->InfluencePoints = $fedServer['Policy']['ForceInfluence'];
            }

            // Apply the ForceAccessLevel policy
            if (isset($fedServer['Policy']['ForceAccessLevel']) && -1 !== $fedServer['Policy']['ForceAccessLevel']) {
                if (isset($character->AccessLevel)) { //AccessLevel is not currently specified in our exports, so it defaults to null right now
                    $character->AccessLevel = $fedServer['Policy']['ForceAccessLevel'];
                }
            }

            // Apply the ForceDefaultMap policy
            if (isset($fedServer['Policy']['ForceDefaultMap']) && false !== $fedServer['Policy']['ForceDefaultMap']) {
                // See https://git.ourodev.com/CoX/Source/src/branch/develop/dbserver/clientcomm.c#L1300-L1310 for starting mapid's
                $character->StaticMapId = 83; // Pocket D
                unset($character->MapId);
                unset($character->PosX);
                unset($character->PosY);
                unset($character->PosZ);
                unset($character->OrientP);
                unset($character->OrientY);
                unset($character->OrientR);
            }

            // Apply the AllowInventory policy
            if (isset($fedServer['Policy']['AllowInventory']) && false === $fedServer['Policy']['AllowInventory']) {
                unset($character->InvSalvage0);
                unset($character->InvRecipeInvention);
            }

            // If all steps succeeded to this point, complete the transfer
            $message = new Message($fedServer['Name']);
            $message->character = $_SESSION['pullcharacter']['character'];

            // Apply the delete policy
            if (true == $fedServer['Policy']['DeleteOnTransfer']) {
                $result = Http::Post($fedServer['Url'].'/api/character/delete', ['message' => json_encode($message)]);
                if ('Success' != $result) {
                    throw new Exception('Deleting character from the remote server failed.');
                }
            } else {
                // Inform origin server to remove the lockout
                Http::Post($fedServer['Url'].'/federation/complete-transfer', ['character' => $_SESSION['pullcharacter']['character']]);
            }

            // Put the character into the database
            $character->PutCharacter();

            return $this->container->get('renderer')->render($HttpResponse, 'core/page-generic-message.phtml', ['title' => 'Welcome to '.getenv('portal_name'), 'message' => $character->Name.' has been transferred successfully!']);
        } catch (Exception $e) {
            return $this->container->get('renderer')->render($HttpResponse, 'core/page-generic-message.phtml', ['title' => 'An Error was encountered', 'message' => $e->GetMessage()]);
        }
    }

    public function ClearTransfer(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        $sql = SqlServer::getInstance();

        $sql->Query('UPDATE cohdb.dbo.Ents2 set AccSvrLock = null FROM cohdb.dbo.Ents INNER JOIN cohdb.dbo.Ents2 ON Ents.ContainerId = Ents2.ContainerId WHERE Ents.Name = ?', array(DataHandling::Decrypt($_POST['character'], getenv('portal_key'), getenv('portal_iv'))));
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
