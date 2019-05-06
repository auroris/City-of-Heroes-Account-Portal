<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\Character;
use App\Util\DataHandling;

class CharacterTransferController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function GetCharacter(Request $request, Response $response, array $args)
    {
        if (isset($args['type']) && 'json' == $args['type']) {
            $newResponse = $response->withHeader('Content-type', 'application/json');
            $character = new Character(DataHandling::Decrypt($args['encrypted_name'], $GLOBALS['crypto']['key'], $GLOBALS['crypto']['iv']));

            return $newResponse->write($character->ToJSON());
        } else {
            $newResponse = $response->withHeader('Content-type', 'text/plain');
            $character = new Character(DataHandling::Decrypt($args['encrypted_name'], $GLOBALS['crypto']['key'], $GLOBALS['crypto']['iv']));

            return $newResponse->write(implode("\n", $character->ToArray()));
        }
    }

    public function PutCharacter(Request $request, Response $response, array $args)
    {
    }

    public function DeleteCharacter(Request $request, Response $response, array $args)
    {
    }
}
