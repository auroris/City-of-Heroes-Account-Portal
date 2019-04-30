<?php
namespace App\CoH;

use App\CoH\SqlServer;
use Exception;

class GameAccount {
    private $username;

    function __construct($username = "") {
        $this->username = $username;
    }

    public function Create($username, $password, \Monolog\Logger $logger)
    {
        $conn = SqlServer::getInstance()->getConnection();

        // Validate username
        try {
            DataSanitization::validateUsername($username);
        }
        catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        // Validate password
        try {
            DataSanitization::validatePassword($password);
        }
        catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
        // Check username uniqueness
        $qCheckAccountUniqueness = sqlsrv_query($conn, "SELECT count(*) FROM cohauth.dbo.user_account WHERE UPPER(account) = UPPER(?)", array($username));
        if (sqlsrv_fetch($qCheckAccountUniqueness) === false) {
            return ['success' => false, 'message' => 'The account name you entered has already been taken.'];
        }

        // Generate a new account ID and password hash
        $qNewAccountUID = sqlsrv_query($conn, "SELECT max(uid) FROM dbo.user_account");
        sqlsrv_fetch($qNewAccountUID);
        $uid = sqlsrv_get_field($qNewAccountUID, 0) + 1;
        $hash = GamePassword::BinPassword($username, $password);

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
            return['success' => false, 'message' => 'Unable to create your account; something went wrong.'];
        } 

        if(sqlsrv_query($conn, $sql2, array($username, $hash)) === false)
        {
            sqlsrv_rollback($conn);
            $logger->error("Error when creating account; could not run " . $sql2 . " [username: '" . $username . "', hash: '" . $hash . "']\n" . print_r(sqlsrv_errors(), true));
            return['success' => false, 'message' => 'Unable to create your account; something went wrong.'];
        } 

        if(sqlsrv_query($conn, $sql3, array($uid)) === false)
        {
            sqlsrv_rollback($conn);
            $logger->error("Error when creating account; could not run " . $sql3 . " [uid: '" . $uid . "']\n" . print_r(sqlsrv_errors(), true));
            return['success' => false, 'message' => 'Unable to create your account; something went wrong.'];
        } 

        if(sqlsrv_query($conn, $sql4, array($uid)) === false)
        {
            sqlsrv_rollback($conn);
            $logger->error("Error when creating account; could not run " . $sql4 . " [uid: '" . $uid . "']\n" . print_r(sqlsrv_errors(), true));
            return['success' => false, 'message' => 'Unable to create your account; something went wrong.'];
        } 

        // All statements executed successfully, commit the transaction and return success.
        sqlsrv_commit($conn);
        $this->username = $username;
        
        return ['success' => true, 'username' => $username, 'uid' => $uid, 'message' => "Account created successfully! You may log in immediately."];
    }

    function Login($username, $password, \Monolog\Logger $logger)
    {
        $conn = SqlServer::getInstance()->getConnection();
        
        // Validate username
        try {
            DataSanitization::validateUsername($username);
        }
        catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        // Validate password
        try {
            DataSanitization::validatePassword($password);
        }
        catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        // Convert password to game format
        $hash = GamePassword::BinPassword($username, $password);
        
        // Verify that the username and password match an account in the database
        $found = sqlsrv_query($conn, "SELECT 1 FROM cohauth.dbo.user_auth WHERE UPPER(account) = UPPER(?) AND convert(varchar, password) = SUBSTRING(?, 1, 30)", array($username, $hash));
        if (sqlsrv_fetch($found) === false)
        {
            $ipaddress = '';
            if (getenv('HTTP_CLIENT_IP')) { $ipaddress = getenv('HTTP_CLIENT_IP'); }
            else if(getenv('HTTP_X_FORWARDED_FOR')) { $ipaddress = getenv('HTTP_X_FORWARDED_FOR'); }
            else if(getenv('HTTP_X_FORWARDED')) { $ipaddress = getenv('HTTP_X_FORWARDED'); }
            else if(getenv('HTTP_FORWARDED_FOR')) { $ipaddress = getenv('HTTP_FORWARDED_FOR'); }
            else if(getenv('HTTP_FORWARDED')) { $ipaddress = getenv('HTTP_FORWARDED'); }
            else if(getenv('REMOTE_ADDR')) { $ipaddress = getenv('REMOTE_ADDR'); }
            else { $ipaddress = 'UNKNOWN'; }
            
            $logger->info("Failed login from " . $ipaddress . " for account " . $username);
            return ['success' => false, 'message' => "That username and password does not match our records."];
        }

        $this->username = $username;
        return ['success' => true, 'username' => $username];
    }
    
    function ChangePassword($newPassword, \Monolog\Logger $logger)
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
        $hash = GamePassword::BinPassword($this->username, $newPassword);

        sqlsrv_query($conn, "UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE UPPER(account) = UPPER(?)", array($hash, $this->username));
        
        return ['success' => true, 'message' => "Your password has been changed successfully."];
    }

    function GetCharacterList(\Monolog\Logger $logger)
    {
        $conn = SqlServer::getInstance()->getConnection();

        $qCharacters = sqlsrv_query($conn, "select * FROM cohdb.dbo.ents WHERE authname = ?", array($this->username));
        $characters = array();
        while($row = sqlsrv_fetch_array($qCharacters, SQLSRV_FETCH_ASSOC)) {
            array_push($characters, $row);
        }

        return $characters;
    }

    function GetUsername()
    {
        return $this->username;
    }
}