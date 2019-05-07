<?php

// Your DB connection information and credentials
$GLOBALS['db'] =
[
    'Server' => '10.5.0.144', //localhost/SQLExpress, if running on same server
    'Database' => 'cohauth',
    'Username' => 'CoHDB',
    'Password' => 'bqaDDMA7QUKNABYdKQrj',
];

// Path to DBQuery or way to start dbquery
$GLOBALS['dbquery'] = 'wine /home/ubuntu/environment/i25Binaries/bin/dbquery.exe -db 10.5.0.144'; //'C:\\Issue25Server\\bin\\dbquery.exe'

$GLOBALS['federation_server'] = 'Aurora Server'; // Our name in other server's Federation array

// List of federation servers
$GLOBALS['federation'] = [
    [
        'Name' => 'Aurora Server',
        'Url' => 'http://localhost:8080/aleena.slim.portal/public',
        'Policy' => [ // Policy for characters coming from 'Aurora Server'
            'ForceInfluence' => 0, // 0 (or any number) to force inf to that number; false to disable and allow whatever the character has
            'ForceAccessLevel' => 0, // 0 (or any number) to force access level to that number; false to disable and allow whatever the character has
            'AllowInventory' => false, // false to delete the inventory, true to allow whatever the character has
        ],
        'Crypto' => [ // Configure the same crypto keys with Aurora Server
            'key' => 'AuroraKey',
            'iv' => 'AuroraVector',
        ],
    ],
    [
        'Name' => 'City Of Heroes Rebirth',
        'Url' => 'https://play.cityofheroesrebirth.com/portal/public',
        'Policy' => [ // Policy for characters coming from CoH Rebirth
            'ForceInfluence' => false, // Allow inf to carry over
            'ForceAccessLevel' => 0, // 0 (or any number) to force access level to that number; false to disable and allow whatever the character has
            'AllowInventory' => true, // Allow inventory to carry over
        ],
        'Crypto' => [ // This server's entry on Rebirth must have the same crypto keys as below, and vice versa.
            'key' => 'RebirthKey',
            'iv' => 'RebirthVector',
        ],
    ],
];

// Cryptographic keys used for internal things, don't share
$GLOBALS['crypto'] = [
    'key' => 'Some Key',
    'iv' => 'Some Initialization Vector',
];

// Slim PHP settings
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__.'/../templates/',
            'cache_path' => false, //__DIR__ . '/../cache/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__.'/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
];
