<?php
namespace App\CoH;

use App\CoH\SqlServer;
use Exception;

class GameAccount {
    private $username;

    function __construct($username = "") {
        $this->username = $username;
    }

    public function create($username, $password, \Monolog\Logger $logger)
    {
        $conn = SqlServer::getInstance()->getConnection();

        // Validate username
        try {
            DataSanitization::validateUsername($username);
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        // Validate password
        try {
            DataSanitization::validatePassword($password);
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        
        // Check username uniqueness
        $qCheckAccountUniqueness = sqlsrv_query($conn, "SELECT 1 FROM cohauth.dbo.user_account WHERE UPPER(account) = UPPER(?)", array($username));
        if (sqlsrv_fetch($qCheckAccountUniqueness) === true) {
            throw new Exception('The account name you entered has already been taken.');
        }

        // Enforce the no duplicate IPs policy
        if ($GLOBALS["policy"]["AllowDuplicateIPs"] === false) {
            $qCheckIPs = sqlsrv_query($conn, "SELECT 1 FROM cohauth.dbo.user_account WHERE last_ip = ?", array(GameAccount::getClientIP()));
            if (sqlsrv_fetch($qCheckIPs) === true) {
                throw new Exception('Policy prohibits more than one registration from your IP address.');
            }
        }

        // Generate a new account ID and password hash
        $qNewAccountUID = sqlsrv_query($conn, "SELECT max(uid) FROM cohauth.dbo.user_account");
        sqlsrv_fetch($qNewAccountUID);
        $uid = sqlsrv_get_field($qNewAccountUID, 0) + 1;
        $hash = GamePassword::binPassword($username, $password);

        // SQL statements to execute
        $sql1 = "INSERT INTO cohauth.dbo.user_account (account, uid, forum_id, pay_stat) VALUES (?, ?, ?, 1014)";
        $sql2 = "INSERT INTO cohauth.dbo.user_auth (account, password, salt, hash_type) VALUES (?, CONVERT(BINARY(128),?), 0, 1)";
        $sql3 = "INSERT INTO cohauth.dbo.user_data (uid, user_data) VALUES (?, 0x0080C2E000D00B0C000000000CB40058)";
        $sql4 = "INSERT INTO cohauth.dbo.user_server_group (uid, server_group_id) VALUES (?, 1)";
        
        // Insert the database data
        sqlsrv_begin_transaction($conn);
       
        if(sqlsrv_query($conn, $sql1, array($username, $uid, $uid)) === false)
        {
            sqlsrv_rollback($conn);
            $logger->error("Error when creating account; could not run " . $sql1 . " [username: '" . $username . "', uid: '" . $uid . "', forum_id: '" . $uid . "']\n" . print_r(sqlsrv_errors(), true));
            throw new Exception('Unable to create your account; something went wrong.');
        } 

        if(sqlsrv_query($conn, $sql2, array($username, $hash)) === false)
        {
            sqlsrv_rollback($conn);
            $logger->error("Error when creating account; could not run " . $sql2 . " [username: '" . $username . "', hash: '" . $hash . "']\n" . print_r(sqlsrv_errors(), true));
            throw new Exception('Unable to create your account; something went wrong.');
        } 

        if(sqlsrv_query($conn, $sql3, array($uid)) === false)
        {
            sqlsrv_rollback($conn);
            $logger->error("Error when creating account; could not run " . $sql3 . " [uid: '" . $uid . "']\n" . print_r(sqlsrv_errors(), true));
            throw new Exception('Unable to create your account; something went wrong.');
        } 

        if(sqlsrv_query($conn, $sql4, array($uid)) === false)
        {
            sqlsrv_rollback($conn);
            $logger->error("Error when creating account; could not run " . $sql4 . " [uid: '" . $uid . "']\n" . print_r(sqlsrv_errors(), true));
            throw new Exception('Unable to create your account; something went wrong.');
        } 

        // All statements executed successfully, commit the transaction.
        sqlsrv_commit($conn);
        $this->username = $username;
    }

    function login($username, $password, \Monolog\Logger $logger)
    {
        $conn = SqlServer::getInstance()->getConnection();
        
        // Validate username
        try {
            DataSanitization::validateUsername($username);
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        // Validate password
        try {
            DataSanitization::validatePassword($password);
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        // Convert password to game format
        $hash = GamePassword::binPassword($username, $password);
        
        // Verify that the username and password match an account in the database
        $found = sqlsrv_query($conn, "SELECT 1 FROM cohauth.dbo.user_auth WHERE UPPER(account) = UPPER(?) AND convert(varchar, password) = SUBSTRING(?, 1, 30)", array($username, $hash));
        if (sqlsrv_fetch($found) === null)
        {
            $logger->info("Failed login from " . GameAccount::getClientIP() . " for account " . $username);
            throw new Exception("That username and password does not match our records.");
        }

        $this->username = $username;
    }
    
    function changePassword($newPassword, \Monolog\Logger $logger)
    {
        $conn = SqlServer::getInstance()->getConnection();
        
        // Validate password
        try {
            DataSanitization::validatePassword($newPassword);
        }
        catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        // Convert password to game format
        $hash = GamePassword::binPassword($this->username, $newPassword);

        sqlsrv_query($conn, "UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE UPPER(account) = UPPER(?)", array($hash, $this->username));
        
        return ['success' => true, 'message' => "Your password has been changed successfully."];
    }

    function getCharacterList(\Monolog\Logger $logger)
    {
        $conn = SqlServer::getInstance()->getConnection();

        $qCharacters = sqlsrv_query($conn, "select * FROM cohdb.dbo.ents WHERE authname = ?", array($this->username));
        $characters = array();
        while($row = sqlsrv_fetch_array($qCharacters, SQLSRV_FETCH_ASSOC)) {
            $row["datauri"] = DataSanitization::encrypt($row["Name"], $GLOBALS["crypto"]["key"], $GLOBALS["crypto"]["iv"]);
            array_push($characters, $row);
        }

        return $characters;
    }

    public static function getCharacter($name) {
        $name = DataSanitization::decrypt($name, $GLOBALS["crypto"]["key"], $GLOBALS["crypto"]["iv"]);

        $conn = SqlServer::getInstance()->getConnection();
        $qCharacterUID = sqlsrv_query($conn, "SELECT ContainerId FROM cohdb.dbo.ents WHERE name = ?", array($name));
        if (sqlsrv_fetch($qCharacterUID) === true) {
            sqlsrv_get_field($qCharacterUID, 0);

            // Check DBQuery's existance
            if (!file_exists($GLOBALS["dbquery"]))
            { throw new Exception("File " . $GLOBALS["dbquery"] . " is not found."); }

            $results = array();
            $ret = 0;
            exec($GLOBALS["dbquery"] . ' -getcharacter ' . escapeshellarg($name), $results, $ret);

            if ($ret != 0) {
                throw new Exception("Calling " . $GLOBALS["dbquery"] . " failed with a return code of " . $ret);
            }

            return $results;
        }
        else {
            throw new Exception('No such character "' . $name . '"');
        }
    }

    function getUsername()
    {
        return $this->username;
    }

    public static function getClientIP()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) { $ipaddress = getenv('HTTP_CLIENT_IP'); }
        else if(getenv('HTTP_X_FORWARDED_FOR')) { $ipaddress = getenv('HTTP_X_FORWARDED_FOR'); }
        else if(getenv('HTTP_X_FORWARDED')) { $ipaddress = getenv('HTTP_X_FORWARDED'); }
        else if(getenv('HTTP_FORWARDED_FOR')) { $ipaddress = getenv('HTTP_FORWARDED_FOR'); }
        else if(getenv('HTTP_FORWARDED')) { $ipaddress = getenv('HTTP_FORWARDED'); }
        else if(getenv('REMOTE_ADDR')) { $ipaddress = getenv('REMOTE_ADDR'); }
        else { $ipaddress = 'UNKNOWN'; }
        return $ipaddress;
    }
}
