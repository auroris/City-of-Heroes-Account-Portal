<?php

namespace App\Util;

use Exception;

class SqlServer
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $this->conn = sqlsrv_connect(getenv('db_server'), array(
                'Database' => getenv('db_database'),
                'Uid' => getenv('db_username'),
                'PWD' => getenv('db_password'), ));
            if (false == $this->conn) {
                die("<pre>Error connecting to '".getenv('db_server')."' with username '".getenv('db_username').'"'."\n\n".print_r(sqlsrv_errors(), true));
            }
        } catch (Exception $e) {
            die("<pre>An exception was thrown while connecting to '".getenv('db_server')."' with username '".getenv('db_username').'"'."\n\n".print_r($e, true));
        }
    }

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new SqlServer();
        }

        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    // Returns a numerical array of rows containing an associative array of columns for each row
    public function FetchAssoc($sql, array $vars = [])
    {
        $query = sqlsrv_query($this->conn, $sql, $vars);
        if (false === $query) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $result = array();
        while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
            array_push($result, $row);
        }

        return $result;
    }

    // Returns a numerical array of rows containing a numerical array of columns for each row
    public function FetchNumeric($sql, array $vars = [])
    {
        $query = sqlsrv_query($this->conn, $sql, $vars);
        if (false === $query) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $result = array();
        while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_NUMERIC)) {
            array_push($result, $row);
        }

        return $result;
    }

    // Return bool (true/false) if the query returns rows. Throws an exception if an error occurs.
    public function ReturnsRows($sql, array $vars = [])
    {
        $query = sqlsrv_query($this->conn, $sql, $vars);
        if (false === $query) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        // Why is sqlsrv_fetch() and sqlsrv_has_rows() so stupid? has_rows looks like it'd work,
        // except it returns false both on error and no rows. sqlsrv_fetch distinguishes,
        // but uses a null to say no rows and false when error. Argh.
        $result = sqlsrv_fetch($query);
        if (true === $result) {
            // A result was returned
            return true;
        } elseif (false === $result) {
            // An error occured
            throw new Exception(print_r(sqlsrv_errors(), true));
        } elseif (null === $result) {
            // No rows returned
            return false;
        }
    }

    // Runs a statement with no return
    public function Query($sql, array $vars = [])
    {
        $query = sqlsrv_query($this->conn, $sql, $vars);
        if (false === $query) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }
    }

    public function BeginTransaction()
    {
        $result = sqlsrv_begin_transaction($this->conn);

        if (false === $result) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }
    }

    public function Rollback()
    {
        $result = sqlsrv_rollback($this->conn);

        if (false === $result) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }
    }

    public function Commit()
    {
        $result = sqlsrv_commit($this->conn);

        if (false === $result) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }
    }
}
