<?php

namespace App\Controller;

use App\Model\GameAccount;
use App\Util\DataHandling;

class AccountTransferController
{
    public function CreateOrUpdateAccount(Request $request, Response $response, array $args)
    {
    }

    public function ListCharacters(Request $request, Response $response, array $args)
    {
        $newResponse = $response->withHeader('Content-type', 'application/json');
        $account = new GameAccount(DataHandling::Decrypt($args['encrypted_name'], getenv('portal_key'), getenv('portal_iv')));

        return $newResponse->write($account->GetCharacterList());
    }
}
