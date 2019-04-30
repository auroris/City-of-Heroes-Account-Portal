<?php
namespace App\CoH;

class GameData {
    public static function CountAccounts()
    {
        try {
            $conn = SqlServer::getInstance()->getConnection();
            $qTotalAccounts = sqlsrv_query($conn, "SELECT count(*) FROM dbo.user_account");
            sqlsrv_fetch($qTotalAccounts);

            return sqlsrv_get_field($qTotalAccounts, 0);
        }
        catch (Exception $e) {
            return -1;
        }
    }

    public static function CountCharacters()
    {
        try {
            $conn = SqlServer::getInstance()->getConnection();
            $qTotalChars = sqlsrv_query($conn, "SELECT count(*) FROM cohdb.dbo.ents");
            sqlsrv_fetch($qTotalChars);

            return sqlsrv_get_field($qTotalChars, 0);
        }
        catch (Exception $e) {
            return -1;
        }
    }

    public static function CountOnline()
    {
        $results = array();
        exec('tasklist /FI "IMAGENAME EQ CHATSERVER.EXE" /FO CSV /V', $results);

        if (isset($results[1])) {
            return explode(' ', $results[1])[10];
        }

        return -1;
    }
}

?>