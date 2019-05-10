<?php

namespace App\Model;

use App\Util\DataHandling;
use App\Util\MonoLogger;
use App\Util\SqlServer;
use Exception;

class GameAccount
{
    private $sql;
    private $logger;
    private $username;
    private $uid;
    private $last_ip;
    private $last_login;
    private $last_logout;

    public function __construct($username = '')
    {
        $this->sql = SqlServer::getInstance();
        $this->logger = MonoLogger::GetLogger();

        if ('' != $username) {
            $qAccount = $this->sql->FetchAssoc('SELECT a.account, a.uid, a.last_login, a.last_logout, a.last_ip FROM cohauth.dbo.user_account a INNER JOIN cohauth.dbo.user_auth b ON a.account = b.account WHERE UPPER(b.account) = UPPER(?)', array($username));
            foreach ($qAccount as $row) {
                $this->username = $row['account'];
                $this->uid = $row['uid'];
                $this->last_ip = $row['last_ip'];
                $this->last_login = $row['last_login'];
                $this->last_logout = $row['last_logout'];
            }
        }
    }

    // Vars to be persisted when serialized to $_SESSION
    public function __sleep()
    {
        return array('username', 'uid', 'last_ip', 'last_login', 'last_logout');
    }

    // Not __wakeup, because that's too early in the php lifecycle for needed stuff to be loaded
    private function wakeup()
    {
        if (!isset($this->sql) || !isset($this->logger)) {
            $this->sql = SqlServer::getInstance();
            $this->logger = MonoLogger::GetLogger();
        }
    }

    public function Create($username, $password)
    {
        $this->wakeup();

        // Validate username & password
        DataHandling::ValidateUsername($username);
        DataHandling::ValidatePassword($password);

        // Check username uniqueness
        if (true === $this->sql->ReturnsRows('SELECT 1 FROM cohauth.dbo.user_account WHERE UPPER(account) = UPPER(?)', array($username))) {
            throw new Exception('The account name you entered has already been taken.');
        }

        // Generate a new account ID and password hash
        $qNewAccountUID = $this->sql->FetchNumeric('SELECT max(uid) FROM cohauth.dbo.user_account');
        $uid = $qNewAccountUID[0][0] + 1;
        $hash = DataHandling::BinPassword($username, $password);

        // SQL statements to execute
        $sql1 = 'INSERT INTO cohauth.dbo.user_account (account, uid, forum_id, pay_stat) VALUES (?, ?, ?, 1014)';
        $sql2 = 'INSERT INTO cohauth.dbo.user_auth (account, password, salt, hash_type) VALUES (?, CONVERT(BINARY(128),?), 0, 1)';
        $sql3 = 'INSERT INTO cohauth.dbo.user_data (uid, user_data) VALUES (?, 0x0080C2E000D00B0C000000000CB40058)';
        $sql4 = 'INSERT INTO cohauth.dbo.user_server_group (uid, server_group_id) VALUES (?, 1)';

        // Insert the database data
        try {
            $this->sql->BeginTransaction();
            $this->sql->Query($sql1, array($username, $uid, $uid));
            $this->sql->Query($sql2, array($username, $hash));
            $this->sql->Query($sql3, array($uid));
            $this->sql->Query($sql4, array($uid));
            $this->sql->Commit();
        } catch (Exception $e) {
            $this->sql->Rollback();
            $this->logger->error('Error creating account: '.print_r($e, true));
            throw new Exception('Unable to create your account; something went wrong.');
        }

        $this->username = $username;
    }

    public function Login($username, $password)
    {
        $this->wakeup();

        // Validate username & password
        DataHandling::ValidateUsername($username);
        DataHandling::ValidatePassword($password);

        // Convert password to game format
        $hash = DataHandling::BinPassword($username, $password);

        // Verify that the username and password match an account in the database
        $qAccount = $this->sql->FetchAssoc('SELECT a.account, a.uid, a.last_login, a.last_logout, a.last_ip FROM cohauth.dbo.user_account a INNER JOIN cohauth.dbo.user_auth b ON a.account = b.account WHERE UPPER(b.account) = UPPER(?) AND CONVERT(VARCHAR, b.password) = SUBSTRING(?, 1, 30)', array($username, $hash));

        foreach ($qAccount as $row) {
            $this->username = $row['account'];
            $this->uid = $row['uid'];
            $this->last_ip = $row['last_ip'];
            $this->last_login = $row['last_login'];
            $this->last_logout = $row['last_logout'];

            return;
        }

        $this->logger->info('Failed login from '.ClientBrowser::GetIP().' for account '.$username);
        throw new Exception('That username and password does not match our records.');
    }

    public function ChangePassword($newPassword)
    {
        $this->wakeup();

        DataHandling::ValidatePassword($newPassword);
        $hash = DataHandling::BinPassword($this->username, $newPassword);
        $this->sql->Query('UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE UPPER(account) = UPPER(?)', array($hash, $this->username));
    }

    public function GetCharacterList()
    {
        $this->wakeup();

        $characters = array();
        $qCharacters = $this->sql->FetchAssoc('select * FROM cohdb.dbo.ents WHERE authname = ?', array($this->username));
        foreach ($qCharacters as $row) {
            $row['datauri'] = urlencode(DataHandling::Encrypt($row['Name'], getenv('portal_key'), getenv('portal_iv')));
            array_push($characters, $row);
        }

        return $characters;
    }

    public function GetUsername()
    {
        $this->wakeup();

        return $this->username;
    }

    public function GetPassword()
    {
        $this->wakeup();
        $qPassword = $this->sql->FetchAssoc('SELECT CONVERT(VARCHAR, password) AS pass FROM cohauth.dbo.user_auth WHERE UPPER(account) = UPPER(?)', array($this->username));

        return $qPassword[0]['pass'];
    }

    public function VerifyHashedPassword($hashedPassword)
    {
        $this->wakeup();

        return $this->sql->ReturnsRows('SELECT 1 FROM cohauth.dbo.user_auth WHERE UPPER(account) = UPPER(?) AND CONVERT(VARCHAR, password) = ?', array($this->username, $hashedPassword));
    }

    public function GetUID()
    {
        return $this->uid;
    }
}
