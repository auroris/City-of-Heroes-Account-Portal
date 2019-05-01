<?php
namespace App\CoH;

class SqlServer {
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $config["db"] =
        [
            'Server' => 'localhost\sqlexpress',
            'Database' => 'cohauth', 
            'Username'=>'CoHDB', 
            'Password'=>'bqaDDMA7QUKNABYdKQrj'
        ];

        try
        {
            $this->conn = sqlsrv_connect($config['db']['Server'], array(
                "Database"=> $config['db']['Database'],  
                "Uid"=> $config['db']['Username'], 
                "PWD"=> $config['db']['Password']));
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