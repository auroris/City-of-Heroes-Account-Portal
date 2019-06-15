<?php

namespace App\Model;

use Exception;
use App\Util\Exec;
use App\Util\SqlServer;

class CoHStats
{
    protected $sql;

    public function __construct()
    {
        $this->sql = SqlServer::getInstance();
    }

    public function CountAccounts()
    {
        try {
            return $this->sql->GetValue('SELECT count(*) FROM cohauth.dbo.user_account');
        } catch (Exception $e) {
            return -1;
        }
    }

    public function CountCharacters()
    {
        try {
            return $this->sql->GetValue('SELECT count(*) FROM cohdb.dbo.ents');
        } catch (Exception $e) {
            return -1;
        }
    }

    public function GetOnline()
    {
        try {
            $qOnline = $this->sql->FetchAssoc('SELECT Ents.Name, Ents.StaticMapId, Ents.AccessLevel, Ents2.LfgFlags FROM cohdb.dbo.Ents INNER JOIN cohdb.dbo.Ents2 ON Ents.ContainerId = Ents2.ContainerId WHERE Ents.Active > 0 ORDER BY Name ASC');

            $arr = array();
            $onlineCount = 0;

            // No rows or error, return 0
            if (0 == count($qOnline)) {
                return ['Count' => 0, 'List' => []];
            }

            foreach ($qOnline as $row) {
                // Skip CSR's if policy requires
                if ($row['AccessLevel'] >= getenv('portal_hide_csr')) {
                    continue;
                }

                // Start counting number of non CSR players
                ++$onlineCount;

                // If LFG Only policy, then list only LFG people: people whose LfgFlag is not null (default not seeking), not 0 (not seeking), and not 128 (do not accept invites).
                if ('true' == getenv('portal_lfg_only') && (!isset($row['LfgFlags']) || 0 == $row['LfgFlags'] || 128 == $row['LfgFlags'])) {
                    continue;
                }

                // Associate map name
                $row['MapName'] = Maps::$ID[$row['StaticMapId']];

                array_push($arr, $row);
            }

            return ['Count' => $onlineCount, 'List' => $arr];
        } catch (Exception $e) {
            return ['Count' => 0, 'List' => []];
        }
    }

    public function GetServerStatus()
    {
        $cmd = getenv('dbquery').' -dbquery';

        try {
            $results = Exec::Exec($cmd, 5);

            if (strlen($results) > 0) {
                $uptime = explode(',', explode("\n", $results)[10]);

                return ['status' => 'Online',
                'uptime' => substr(trim($uptime[1].' '.$uptime[2]), 3),
                'started' => trim(substr($uptime[0], 20)), ];
            } else {
                return ['status' => 'Offline'];
            }
        } catch (Exception $e) {
            return ['status' => 'broken - '.$e->getMessage()];
        }
    }
}
