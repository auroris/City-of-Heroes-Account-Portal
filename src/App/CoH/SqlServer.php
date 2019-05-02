<?php
namespace App\CoH;

class SqlServer {
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try
        {
            $this->conn = sqlsrv_connect($GLOBALS["db"]['Server'], array(
                "Database"=> $GLOBALS["db"]['Database'],  
                "Uid"=> $GLOBALS["db"]['Username'], 
                "PWD"=> $GLOBALS["db"]['Password']));
            if($this->conn == false)  
                die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
        }
        catch(Exception $e)
        {  
            die(print_r($e));
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new SqlServer();
        }

        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
?>