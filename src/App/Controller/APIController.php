<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\Character;
use App\Util\DataHandling;
use App\Messages\Message;
use App\Util\SqlServer;
use Exception;

class APIController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function GetCharacter(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        $character = new Character(DataHandling::Decrypt($_GET['q'], getenv('portal_key'), getenv('portal_iv')));

        if (isset($args['type']) && 'json' == $args['type']) {
            $newResponse = $HttpResponse->withHeader('Content-type', 'application/json');

            return $newResponse->write($character->ToJSON());
        } else {
            $newResponse = $HttpResponse->withHeader('Content-type', 'text/plain');

            return $newResponse->write(implode("\n", $character->ToArray()));
        }
    }

    public function DeleteCharacter(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        try {
            $message = new Message();
            $message->Unserialize($_POST['message']);

            $characterName = DataHandling::Decrypt(urldecode($message->character), getenv('portal_key'), getenv('portal_iv'));
            $character = new Character($characterName);
            $characterData = implode("\n", $character->ToArray());

            // Get the relevant ID's
            $sql = SqlServer::getInstance();
            $authId = $sql->FetchNumeric(
                'SELECT AuthId, ContainerId FROM cohdb.dbo.Ents WHERE Name = ?',
                array($characterName)
            );

            // Verify that no characters from the account are logged in
            if ($sql->ReturnsRows('SELECT 1 FROM cohdb.dbo.Ents WHERE AuthId = ? AND Active > 0', $authId[0][0])) {
                throw new Exception('Account must be logged off.');
            }

            // Verify that the transfer lock is in place
            if (!$sql->ReturnsRows('SELECT AccSvrLock FROM cohdb.dbo.Ents2 WHERE ContainerId = ? AND AccSvrLock IS NOT NULL', array($containerId[0][0]))) {
                throw new Exception('The transfer lock does not appear to be in place, the request cannot proceed.');
            }

            // Verify the backups dir exists and is writable
            if (false != realpath('./../backups')) {
                $backupdir = realpath('./../backups').'/'.$character->AuthName;
                if (!file_exists($backupdir)) {
                    if (!mkdir($backupdir)) {
                        throw new Exception('Unable to create backupdir');
                    }
                }

                // Specify backup file
                $backupFile = $backupdir.'/'.preg_replace('/[^a-z0-9]+/', '-', strtolower($character->Name)).'.'.md5($characterData).'.txt';

                // Attempt to write the backup file
                if (false === file_put_contents(
                    $backupFile,
                    $characterData
                )) {
                    throw new Exception('Unable to create backup file');
                }

                // Hide the character by setting the AuthId to a negative version of the normal AuthId
                $sql->Query(
                    'UPDATE cohdb.dbo.Ents SET AuthId = ? WHERE Name = ?',
                    array(-$authId[0][0], $character->Name)
                );

                // Transfer block is no longer needed, so remove that
                $sql->Query(
                    'UPDATE cohdb.dbo.Ents2 SET AccSvrLock = null WHERE ContainerId = ?',
                    array($authId[0][1])
                );

                // Succees!
                $newResponse = $HttpResponse->withHeader('Content-type', 'text/plain');

                return $newResponse->write('Success');
            }
        } catch (Exception $e) {
            // Failure!
            $newResponse = $HttpResponse->withHeader('Content-type', 'text/plain');

            return $newResponse->write('Failure: '.$e->getMessage());
        }
    }
}
