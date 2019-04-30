<?php
namespace App\CoH;

class GamePassword {
    /* Checksum algorithm */
    static function adler32($data)
    {
        $mod_adler = 65521;
        $a = 1;
        $b = 0;
        $len = strlen($data);
        for($index = 0; $index < $len; $index++)
        {
            $a = ($a + ord($data[$index])) % $mod_adler;
            $b = ($b + $a) % $mod_adler;
        }

        return ($b << 16) | $a;
    }

    /* Generate password hash */
    static function HashPassword($authname, $password)
    {
        $authname = strtolower($authname);
        $a32 = GamePassword::adler32($authname);
        $a32hex = sprintf('%08s', dechex($a32));
        $a32hex = substr($a32hex, 6, 2) . substr($a32hex, 4, 2) . substr($a32hex, 2, 2) . substr($a32hex, 0, 2);
        $digest = hash('sha512', $password . $a32hex, TRUE);
        return $digest;
    }

    static function BinPassword($authname, $password) {
        return bin2hex(GamePassword::HashPassword($authname, $password)); 
    }
}
?>