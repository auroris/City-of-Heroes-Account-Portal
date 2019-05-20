<?php

namespace App\Util;

// Example use: \App\Util\MonoLogger::GetLogger()->debug('Hello world!');
// Documentation: https://github.com/Seldaek/monolog

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
