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

    public static function encrypt($text, $key, $iv)
    {
        $method = "AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($method);
        $key = substr(hash('sha256', $key), 0, $ivlen);
        $iv = substr(hash('sha256', $iv), 0, $ivlen);
        return urlencode(openssl_encrypt($text, $method, $key, 0, $iv));
    }

    public static function decrypt($text, $key, $iv)
    {
        $method = "AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($method);
        $key = substr(hash('sha256', $key), 0, $ivlen);
        $iv = substr(hash('sha256', $iv), 0, $ivlen);
        return openssl_decrypt($text, $method, $key, 0, $iv);
    }
}

?>