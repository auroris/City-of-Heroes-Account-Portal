<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\Character;
use App\Util\DataHandling;
use Exception;

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
        $character = new Character(DataHandling::Decrypt($args['encrypted_name'], getenv('portal_key'), getenv('portal_iv')));
        if (isset($args['type']) && 'json' == $args['type']) {
            $newResponse = $response->withHeader('Content-type', 'application/json');

            return $newResponse->write($character->ToJSON());
        }
        $newResponse = $response->withHeader('Content-type', 'text/plain');

        return $newResponse->write(implode("\n", $character->ToArray()));
    }

    // FIXME: Add JSON support?
    public function PutCharacter(Request $request, Response $response, array $args)
    {
        $name = DataHandling::Decrypt($args['encrypted_name'], getenv('portal_key'), getenv('portal_iv'));
        $arrtributes = $request->getParedBody();

        // FIXME: Return HTTP error response instead?
        if (isset($attributes['name']) && $name != $attributes['name']) {
            throw(new Exception('post name and query name differ'));
        }
        $character = new Character($name);
        $character->attributes = $attributes;
        $character->reconstruct();
        $character->unmartial();
    }

    public function DeleteCharacter(Request $request, Response $response, array $args)
    {
    }
}
