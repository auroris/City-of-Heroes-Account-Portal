<?php
namespace App\CoH;

class GameData {
    public static function countAccounts() {
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

    public static function countCharacters() {
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

    public static function countOnline() {
        try { 
            $conn = SqlServer::getInstance()->getConnection(); 
            $qOnline = sqlsrv_query($conn, "SELECT count(*) FROM cohdb.dbo.ents WHERE Active > 0"); 
            sqlsrv_fetch($qOnline); 
            
            return sqlsrv_get_field($qOnline, 0); 
        } 
        catch (Exception $e) { 
            return -1; 
        }
    }

    public static function getCharacter($name, $container) {
        $conn = SqlServer::getInstance()->getConnection();
        $qCharacterUID = sqlsrv_query($conn, "SELECT ContainerId FROM cohdb.dbo.ents WHERE name = ?", array($name));
        if (sqlsrv_fetch($qCharacterUID) === true) {
            sqlsrv_get_field($qCharacterUID, 0);

            $results = array();
            exec($container->get('settings')['dbquery'] . ' -getcharacter ' . escapeshellarg($name), $results);

            return $results;
        }
        else {
            return ['no such character'];
        }
    }
}

?>
