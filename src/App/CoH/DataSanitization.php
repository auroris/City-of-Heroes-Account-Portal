<?php
namespace App\CoH;

use \Exception;

class DataSanitization {
    
    public static function validateUsername($username)
    {
        if (!ctype_alnum($username))
        {
            throw new Exception('Username must consist of letters and numbers only; no spaces or symbols.');
        }

        if (strlen($username) == 0 || strlen($username) > 14)
        {
            throw new Exception('Username must be 1-14 characters in length.');
        }
    }

    public static function validatePassword($password)
    {
        if (!ctype_print($password))
        {
            throw new Exception('Password must consist only of letters, numbers, and symbols.');
        }

        if (strlen($password) < 8 || strlen($password) > 16)
        {
            throw new Exception("Password must be 8-16 characters in length.");
        }
    }
}

?>