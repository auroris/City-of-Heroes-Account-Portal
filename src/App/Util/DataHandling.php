<?php

namespace App\Util;

use Exception;

class DataHandling
{
    public static function ValidateUsername($username)
    {
        if (!ctype_alnum($username)) {
            throw new Exception('Username must consist of letters and numbers only; no spaces or symbols.');
        }

        if (0 == strlen($username) || strlen($username) > 14) {
            throw new Exception('Username must be 1-14 characters in length.');
        }
    }

    public static function ValidatePassword($password)
    {
        if (!ctype_print($password)) {
            throw new Exception('Password must consist only of letters, numbers, and symbols.');
        }

        if (strlen($password) < 8 || strlen($password) > 16) {
            throw new Exception('Password must be 8-16 characters in length.');
        }
    }

    /* Checksum algorithm */
    private static function Adler32($data)
    {
        $mod_adler = 65521;
        $a = 1;
        $b = 0;
        $len = strlen($data);
        for ($index = 0; $index < $len; ++$index) {
            $a = ($a + ord($data[$index])) % $mod_adler;
            $b = ($b + $a) % $mod_adler;
        }

        return ($b << 16) | $a;
    }

    /* Generate password hash */
    public static function HashPassword($authname, $password)
    {
        $authname = strtolower($authname);
        $a32 = DataHandling::adler32($authname);
        $a32hex = sprintf('%08s', dechex($a32));
        $a32hex = substr($a32hex, 6, 2).substr($a32hex, 4, 2).substr($a32hex, 2, 2).substr($a32hex, 0, 2);
        $digest = hash('sha512', $password.$a32hex, true);

        return $digest;
    }

    public static function BinPassword($authname, $password)
    {
        return bin2hex(DataHandling::HashPassword($authname, $password));
    }

    public static function Encrypt($text, $key, $iv)
    {
        $method = 'AES-256-CBC';
        $ivlen = openssl_cipher_iv_length($method);
        $key = substr(hash('sha256', $key), 0, $ivlen);
        $iv = substr(hash('sha256', $iv), 0, $ivlen);

        return openssl_encrypt($text, $method, $key, 0, $iv);
    }

    public static function Decrypt($text, $key, $iv)
    {
        $method = 'AES-256-CBC';
        $ivlen = openssl_cipher_iv_length($method);
        $key = substr(hash('sha256', $key), 0, $ivlen);
        $iv = substr(hash('sha256', $iv), 0, $ivlen);

        return openssl_decrypt($text, $method, $key, 0, $iv);
    }
}
