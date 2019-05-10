<?php

namespace App\Util;

class MonoLogger
{
    private static $logger;

    public static function SetLogger(\Monolog\Logger $logger)
    {
        MonoLogger::$logger = $logger;
    }

    public static function GetLogger()
    {
        return MonoLogger::$logger;
    }
}
