<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use App\Util\Http;
use App\Util\DataHandling;

class FederationController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function BeginCharacterTransfer(Request $request, Response $response, array $args)
    {
        $fedServer = FindFederationServerByName($_POST['server']);

        $_SESSION['account']->GetUsername;

        die(Http::Post($fedServer['Url'].'/federation/check-account', $this->CreateMessage($_POST['server'], ['username' => $_SESSION['account']->GetUsername(), 'password' => $_SESSION['account']->GetPassword()])));
    }

    public function CheckAccount(Request $request, Response $response, array $args)
    {
        die($this->OpenMessage($_POST['Server'], $_POST['Message']));
    }

    // Find a federation server by its name.
    private function FindFederationServerByName($name)
    {
        foreach ($GLOBALS['federation'] as $item) {
            if (false !== stripos($item['Name'], $name)) {
                return $item;
            }
        }

        throw new Exception('Unknown federation server ' + $name);
    }

    // Given a server's name, look up the pre-agreed crypto keys and encrypt the json-encoded string of the array passed
    private function CreateMessage($addressTo, $args = [])
    {
        $fedServer = $this->FindFederationServerByName($addressTo);

        $message = ['Server' => $GLOBALS['federation_server'],
            'Message' => DataHandling::Encrypt(json_encode($args), $fedServer['Crypto']['key'], $fedServer['Crypto']['iv']),
        ];
    }

    // Given a server's name, look up the pre-agreed crypto keys and decrypt the json-encoded string into an array
    private function OpenMessage($addressFrom, $message)
    {
        $fedServer = $this->FindFederationServerByName($addressFrom);

        return json_decode(DataHandling::Decrypt($message, $fedServer['Crypto']['key'], $fedServer['Crypto']['iv']));
    }
}
