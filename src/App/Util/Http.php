<?php

namespace App\Util;

class Http
{
    public static function Get($url)
    {
        return file_get_contents($url, false);
    }

    public static function Post($url, $args = [])
    {
        $opts = array(
            'http' => array('method' => 'post'),
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $args,
        );
        $context = stream_context_create($opts);

        return file_get_contents($url, false, $context);
    }
}
