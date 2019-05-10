<?php

namespace App\Util;

use Exception;

class Http
{
    public static function Get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if (200 !== curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            throw new Exception($url."\n".print_r($response, true));
        }
        curl_close($ch);

        return $response;
    }

    public static function Post($url, array $args = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        $response = curl_exec($ch);
        if (200 !== curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            die('<h1>Error from remote site</h1>'.print_r($response, true));
        }
        curl_close($ch);

        return $response;
    }
}
