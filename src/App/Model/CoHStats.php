<?php

namespace App\Model;

use Exception;
use App\Util\Exec;

class CoHStats
{
    protected $conn;

    public function __construct()
    {
        $this->conn = \App\Util\SqlServer::getInstance()->getConnection();
    }

    public function CountAccounts()
    {
        try {
            $qTotalAccounts = sqlsrv_query($this->conn, 'SELECT count(*) FROM cohauth.dbo.user_account');
            sqlsrv_fetch($qTotalAccounts);

            return sqlsrv_get_field($qTotalAccounts, 0);
        } catch (Exception $e) {
            return -1;
        }
    }

    public function CountCharacters()
    {
        try {
            $qTotalChars = sqlsrv_query($this->conn, 'SELECT count(*) FROM cohdb.dbo.ents');
            sqlsrv_fetch($qTotalChars);

            return sqlsrv_get_field($qTotalChars, 0);
        } catch (Exception $e) {
            return -1;
        }
    }

    public function GetOnline()
    {
        try {
            $qOnline = sqlsrv_query($this->conn, 'SELECT Name FROM cohdb.dbo.ents WHERE Active > 0 ORDER BY Name ASC');
            $arr = array();

            while ($row = sqlsrv_fetch_array($qOnline, SQLSRV_FETCH_NUMERIC)) {
                array_push($arr, $row[0]);
            }

            return $arr;
        } catch (Exception $e) {
            return -1;
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
            return ['status' => 'Broken'];
        }
    }
}
