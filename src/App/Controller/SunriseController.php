<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Util\SqlServer;
use App\Model\CoHStats;

//https://github.com/warpshotcoh/sunrise-specs
class SunriseController
{
    protected $container;
    private $sql;

    public function __construct(ContainerInterface $container)
    {
        $this->sql = SqlServer::getInstance();
        $this->container = $container;
    }

    public function Manifest(Request $request, Response $response, array $args)
    {
        setlocale(LC_TIME, 'Zulu');
        $newResponse = $response->withHeader('Content-type', 'text/xml');

        return $this->container->get('renderer')->render($newResponse, 'sunrise\manifest.xml',
        [
            'portal_name' => getenv('portal_name'),
        ]);
    }

    public function Uptime(Request $request, Response $response, array $args)
    {
        setlocale(LC_TIME, 'Zulu');

        $authServer = '';
        $gameServer = '';
        $newResponse = $response->withHeader('Content-type', 'text/xml');

        // TODO: Authserver check
        $authServer .= '<server type="auth">';
        $authServer .= '<available value="true" />';
        $authServer .= '</server>';

        $qServer = $this->sql->FetchAssoc('SELECT name, inner_ip FROM cohauth.dbo.server');
        foreach ($qServer as $row) {
            $gameServer .= '<server type="game">';
            $gameServer .= '<name>'. $row['name'] . '</name>';

            $gameStats = new \App\Model\CoHStats();
            if ($gameStats->GetServerStatus()['status'] == 'Online')
            {
                $gameServer .= '<available value="true" />';
            }
            else
            {
                $gameServer .= '<available value="false" />';
            }

            // TODO: Query other SQL servers for their data?
            $qOnline = $this->sql->FetchNumeric('SELECT count(*) FROM cohdb.dbo.ents WHERE Active > 0');

            $gameServer .= '<players current="' . $qOnline[0][0] . '" />';
            $gameServer .= '</server>';
        }

        return $this->container->get('renderer')->render($newResponse, 'sunrise\uptime.xml',
        [
            'portal_name' => getenv('portal_name'),
            'zulu_time' => strftime('%Y-%m-%dT%H:%M:%SZ'),
            'auth_server' => $authServer,
            'game_server' => $gameServer,
        ]);
    }
}
